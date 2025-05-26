<?php

use Tygh\Registry;
use Tygh\Navigation\LastView;
use Tygh\Addons\ArmiShipping\ArmiApiHelper; // Assuming ArmiApiHelper is in this namespace

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'create_armi_order') {
        $order_id = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;
        $vehicle_type_id = isset($_REQUEST['armi_vehicle_type']) ? (int)$_REQUEST['armi_vehicle_type'] : null; // From vendor selection

        if (empty($order_id) || $vehicle_type_id === null) {
            fn_set_notification('E', __('error'), __('armi_missing_order_creation_data')); // Add lang var
            return [CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_id];
        }

        $order_info = fn_get_order_info($order_id, false, true, false, true);
        if (empty($order_info)) {
            fn_set_notification('E', __('error'), __('order_not_found'));
            return [CONTROLLER_STATUS_REDIRECT, 'orders.manage'];
        }

        // Find the Armi shipping method used in this order to get service_params
        $armi_shipping_info = null;
        if (!empty($order_info['shipping'])) {
            foreach ($order_info['shipping'] as $shipping_method_instance) {
                if (isset($shipping_method_instance['module']) && $shipping_method_instance['module'] == 'armi_shipping') {
                    // Need to fetch full shipping data to get service_params
                    $full_shipping_data = fn_get_shipping_info($shipping_method_instance['shipping_id'], DESCR_SL);
                    if ($full_shipping_data && !empty($full_shipping_data['service_params'])) {
                        $armi_shipping_info = $full_shipping_data;
                        break;
                    }
                }
            }
        }

        if (empty($armi_shipping_info) || empty($armi_shipping_info['service_params'])) {
            fn_set_notification('E', __('error'), __('armi_shipping_settings_not_found_for_order')); // Add lang var
            return [CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_id];
        }

        $service_params = $armi_shipping_info['service_params'];
        $vendor_api_key = isset($service_params['armi_api_key']) ? $service_params['armi_api_key'] : '';
        $vendor_country_code = isset($service_params['armi_country_code']) ? $service_params['armi_country_code'] : '';
        $vendor_business_id = isset($service_params['armi_business_id']) ? $service_params['armi_business_id'] : '';
        // $vendor_branch_office_id = isset($service_params['armi_branch_office_id']) ? $service_params['armi_branch_office_id'] : ''; // Origin for cost, might not be needed for order creation if API takes business_id

        if (empty($vendor_api_key) || empty($vendor_country_code) || empty($vendor_business_id)) {
            fn_set_notification('E', __('error'), __('armi_vendor_credentials_incomplete')); // Add lang var
            return [CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_id];
        }

        // Get customer destination coordinates (assuming they are stored in armi_order_data or order_info after checkout)
        $armi_order_data_db = fn_armi_shipping_get_order_data($order_id);
        $customer_lat = isset($armi_order_data_db['customer_destination_latitude']) ? $armi_order_data_db['customer_destination_latitude'] : null;
        $customer_lng = isset($armi_order_data_db['customer_destination_longitude']) ? $armi_order_data_db['customer_destination_longitude'] : null;

        if ($customer_lat === null || $customer_lng === null) {
            // Fallback: check if coordinates were stored directly in order_info.shippings_extra (less likely for permanent storage)
            // This part depends on how checkout map data is finalized and stored for the order.
            // For now, we rely on armi_order_data.
            fn_set_notification('E', __('error'), __('armi_customer_destination_coordinates_not_found')); // Add lang var
            return [CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_id];
        }
        
        // Construct Armi API payload for /monitor/order/create
        // Refer to techContext.md and Campos.pdf for payload structure
        $products_payload = [];
        foreach ($order_info['products'] as $product) {
            $products_payload[] = [
                'product_id' => $product['product_id'], // Or product_code if Armi expects that
                'name' => $product['product'],
                'quantity' => $product['amount'],
                'unit_value' => $product['price'], // Price per unit
                // 'observations' => '', // Optional
                // 'image_url' => fn_get_image_pairs($product['product_id'], 'product', 'M', true, true, DESCR_SL)['detailed']['image_path'] ?? '', // Optional
            ];
        }

        // Determine Armi payment_method
        // Default to '3' (Online Transaction) if CS-Cart order is prepaid.
        // This logic needs refinement based on how CS-Cart payment methods are identified as prepaid.
        $armi_payment_method = '3'; // Default
        // Example: if ($order_info['payment_method_data']['processor_params']['is_offline'] == 'Y') { $armi_payment_method = '1'; /* Efectivo */ }


        $payload = [
            'business_id' => $vendor_business_id,
            'total_value' => $order_info['total'], // Total order value
            'delivery_value' => $order_info['shipping_cost'], // Shipping cost charged to customer
            'vehicle_type' => $vehicle_type_id,
            'payment_method' => $armi_payment_method, // Needs robust logic
            'products' => $products_payload,
            'client_info' => [
                'name' => $order_info['s_firstname'] . ' ' . $order_info['s_lastname'],
                'phone' => $order_info['s_phone'] ?: $order_info['b_phone'],
                'email' => $order_info['email'],
                'address' => $order_info['s_address'] . ' ' . $order_info['s_address_2'], // Full address for reference
                'latitude' => (string)$customer_lat,
                'longitude' => (string)$customer_lng,
                // 'observations' => $order_info['notes'] ?? '', // Optional
            ],
            'country' => $vendor_country_code, // This might be part of header, or body, check Armi docs
            'city_code' => $order_info['s_city'], // Or a specific city code if Armi uses that
            'programmed_date' => '', // For scheduled deliveries, empty for immediate
            'programmed_time' => '', // For scheduled deliveries
            'requires_signature' => false, // Example
            'requires_photo' => false,     // Example
            'external_order_id' => (string)$order_id // Link to CS-Cart order
        ];

        $response = ArmiApiHelper::createOrder($payload, $vendor_api_key, $vendor_country_code);

        if ($response && !isset($response['_error']) && isset($response['data']['orderId'])) {
            // Success
            fn_armi_shipping_update_order_data($order_id, [
                'armi_order_id' => $response['data']['orderId'],
                'armi_last_status_code' => $response['data']['status'] ?? 0, // Assuming status is returned
                'armi_vehicle_type_id' => $vehicle_type_id,
                'armi_payment_method_id' => (int)$armi_payment_method
            ]);
            fn_set_notification('N', __('notice'), __('armi_order_created_successfully', ['[armi_order_id]' => $response['data']['orderId']]));
        } else {
            // Failure
            $error_message = __('armi_order_creation_failed_generic'); // Add lang var
            if (isset($response['response']['message'])) {
                $error_message = $response['response']['message'];
            } elseif (isset($response['message'])) {
                $error_message = $response['message'];
            }
            fn_set_notification('E', __('error'), __('armi_order_creation_failed', ['[error]' => $error_message]));
        }

        return [CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_id];
    }

    // Add other modes like 'cancel_armi_order', 'get_armi_status' later
}

// Default redirect if no mode matches or not a POST request for handled modes
return [CONTROLLER_STATUS_REDIRECT, 'orders.manage'];
