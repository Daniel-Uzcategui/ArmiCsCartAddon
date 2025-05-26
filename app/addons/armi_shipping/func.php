<?php

use Tygh\Registry;
use Tygh\Shippings\IService;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Install function for the Armi Shipping addon.
 * This function is called when the addon is installed.
 */
function fn_armi_shipping_install()
{
    // The tables are created via addon.xml <queries> section.
    // Here we can register the shipping carrier and service.

    $service_data = [
        'status' => 'A', // Active
        'module' => 'armi_shipping', // Corresponds to the addon ID
        'code'   => 'armi_delivery', // Unique code for this shipping service
        'sp_file' => '', // No separate processor file, logic will be in this addon
        'description' => __('shipping_armi_delivery'), // Name of the shipping method
        'localization' => '',
    ];

    $service_id = db_query("INSERT INTO ?:shipping_services ?e", $service_data);

    if ($service_id) {
        $shipping_data = [
            'shipping'      => __('shipping_armi_delivery'),
            'service_id'    => $service_id,
            'destination'   => 'I', // International & Local
            'status'        => 'A', // Active
            'company_id'    => 0, // Available to all companies initially
            'position'      => 0,
            'usergroup_ids' => '0', // All usergroups
            'localization'  => '',
            'rate_calculation' => 'R', // Real-time calculation
            'carrier'       => 'armi_logistics', // Corresponds to the carrier code
            'service_params' => serialize([]), // Ensure it's an empty serialized array
        ];
        db_query("INSERT INTO ?:shippings ?e", $shipping_data);

        // Language variables for carrier and service are defined in addon.xml
        // and handled by CS-Cart's core installation process.
        // Manual insertion here is not needed and was targeting an incorrect table.
    }
}

/**
 * Uninstall function for the Armi Shipping addon.
 * This function is called when the addon is uninstalled.
 */
function fn_armi_shipping_uninstall()
{
    // The tables are dropped via addon.xml <queries> section.
    // Here we remove the shipping carrier and service.

    $service_ids = db_get_fields("SELECT service_id FROM ?:shipping_services WHERE module = ?s", 'armi_shipping');
    if (!empty($service_ids)) {
        db_query("DELETE FROM ?:shipping_services WHERE service_id IN (?n)", $service_ids);
        db_query("DELETE FROM ?:shippings WHERE service_id IN (?n)", $service_ids);
        // Rates, rate dependencies, etc., are usually handled by CS-Cart's shipping deletion logic.
    }

    // Note: Language variables defined in addon.xml are typically removed automatically by CS-Cart.
    // Manual deletion from a custom/incorrect table is not needed.
}

/**
 * Retrieves Armi settings for a specific vendor.
 *
 * Retrieves Armi-specific data for a CS-Cart order.
 *
 * @param int $order_id CS-Cart order ID.
 * @return array|false Armi order data or false if not found.
 */
function fn_armi_shipping_get_order_data($order_id)
{
    if (empty($order_id)) {
        return false;
    }
    $data = db_get_row("SELECT * FROM ?:armi_order_data WHERE order_id = ?i", $order_id);
    return $data;
}

/**
 * Updates or inserts Armi-specific data for a CS-Cart order.
 *
 * @param int $order_id CS-Cart order ID.
 * @param array $armi_data Data to update/insert.
 * @return bool True on success, false on failure.
 */
function fn_armi_shipping_update_order_data($order_id, $armi_data)
{
    if (empty($order_id) || empty($armi_data)) {
        return false;
    }

    $existing_data = fn_armi_shipping_get_order_data($order_id);

    if ($existing_data) {
        return db_query("UPDATE ?:armi_order_data SET ?u WHERE order_id = ?i", $armi_data, $order_id);
    } else {
        $armi_data['order_id'] = $order_id;
        return db_query("INSERT INTO ?:armi_order_data ?e", $armi_data);
    }
}

/**
 * Makes a request to the Armi API.
 *
 * @param string $endpoint The API endpoint (e.g., '/order/delivery-cost').
 * @param array $data The data to send in the request body (for POST).
 * @param string $method HTTP method ('GET', 'POST', 'DELETE', etc.).
// More functions for specific logic (shipping calculation, order actions, etc.) will be added later.
// And hooks will be registered in init.php.

/**
 * Makes a request to the Armi API using provided parameters for authentication.
 *
 * @param string $endpoint The API endpoint (e.g., '/order/delivery-cost').
 * @param array  $data The data to send in the request body (for POST).
 * @param string $method HTTP method ('GET', 'POST', 'DELETE', etc.).
 * @param string $api_key The Armi API key.
 * @param string $country_code The country code for the API request.
 * @return array|false The decoded JSON response or false on failure.
 */
function fn_armi_shipping_api_request_with_params($endpoint, $data = [], $method = 'POST', $api_key = '', $country_code = '')
{
    $api_base_url = Registry::get('addons.armi_shipping.armi_api_base_url');
    if (empty($api_base_url)) {
        fn_log_event('armi_shipping', 'api_error', ['message' => 'Armi API Base URL is not configured.']);
        return false;
    }

    if (empty($api_key) || empty($country_code)) {
        fn_log_event('armi_shipping', 'api_error', ['message' => 'Armi API key or country code not provided for API request.']);
        return false;
    }

    $url = rtrim($api_base_url, '/') . '/' . ltrim($endpoint, '/');
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'armi-business-api-key: ' . $api_key,
        'country: ' . $country_code,
    ];

    $request_data = !empty($data) ? json_encode($data) : '';

    $response = Tygh\Http::request($method, $url, $request_data, [
        'headers' => implode("\r\n", $headers),
        'timeout' => 15, // 15 seconds timeout
    ]);

    $http_status = Tygh\Http::getStatus();

    if (empty($response)) {
        fn_log_event('armi_shipping', 'api_error', [
            'endpoint' => $endpoint,
            'method' => $method,
            'status' => $http_status,
            'message' => 'Empty response from Armi API.',
            'request_data' => $data,
        ]);
        return false;
    }

    $decoded_response = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        fn_log_event('armi_shipping', 'api_error', [
            'endpoint' => $endpoint,
            'method' => $method,
            'status' => $http_status,
            'message' => 'Failed to decode JSON response from Armi API.',
            'response_raw' => $response,
            'request_data' => $data,
        ]);
        return false;
    }
    
    if ($http_status < 200 || $http_status >= 300) {
         fn_log_event('armi_shipping', 'api_error', [
            'endpoint' => $endpoint,
            'method' => $method,
            'status' => $http_status,
            'message' => 'Armi API returned non-successful HTTP status.',
            'response' => $decoded_response,
            'request_data' => $data,
        ]);
        return false; 
    }

    return $decoded_response;
}


// HOOK IMPLEMENTATIONS

// Placeholder for orders_details_post_processor hook
function fn_armi_shipping_orders_details_post_processor($order, &$additional_data)
{
    // TODO: Implement logic to add Armi order info and actions to order details page
    // This will involve fetching data from ?:armi_order_data
    // And assigning it to $additional_data or directly to Tygh::$app['view']
    // Example:
    // $armi_order_info = fn_armi_shipping_get_order_data($order['order_id']);
    // if ($armi_order_info) {
    //     Tygh::$app['view']->assign('armi_order_info', $armi_order_info);
    // }
    // Tygh::$app['view']->assign('armi_vehicle_types', [1 => 'Bicicleta', 2 => 'Motocicleta', 3 => 'Carro']); // From Campos.pdf
    // Tygh::$app['view']->assign('armi_payment_methods', [/* ...map from Campos.pdf... */]);
}

// Placeholder for checkout_process_step_post hook
function fn_armi_shipping_checkout_process_step_post($cart, $auth, $checkout_steps, $step_id)
{
    // TODO: If customer selected lat/lng on map, store it in session/cart
    // Example: if (!empty($_REQUEST['armi_destination_lat']) && !empty($_REQUEST['armi_destination_lng'])) {
    //    Tygh::$app['session']['cart']['shippings_extra']['armi_destination'] = [
    //        'lat' => $_REQUEST['armi_destination_lat'],
    //        'lng' => $_REQUEST['armi_destination_lng'],
    //    ];
    // }
}

// Placeholder for calculate_cart_content_before_shipping hook
function fn_armi_shipping_calculate_cart_content_before_shipping(&$cart, $auth, $calculate_shipping_rates)
{
    // TODO: Ensure any data needed for shipping (like lat/lng from session) is properly in $cart if required by IService::calculateRate
    // The current ArmiShipping::calculateRate directly accesses session, so this might not be strictly needed
    // unless we want to move that data into $cart['user_data'] or similar.
}

// Placeholder for checkout_shipping_rates_post hook
function fn_armi_shipping_checkout_shipping_rates_post($group, $shipping_methods, $cart, $auth, &$result_shipping_methods)
{
    // TODO: If Armi shipping is selected, potentially modify template variables here
    // to trigger JS for map display or hiding address fields.
    // Or, this can be handled by JS directly observing shipping method selection.
}

/**
 * Hook function for shippings_configure_post.
 * Used to add JavaScript for the Armi shipping method configuration tab.
 *
 * @param int $shipping_id The ID of the shipping method being configured.
 * @param array $shipping_data The data of the shipping method.
 * @param string $module The module name of the shipping service.
 */
function fn_armi_shipping_shippings_configure_post($shipping_id, $shipping_data, $module)
{
    // Check if the current shipping service module is 'armi_logistics'
    if ($module == 'armi_shipping') {
        // No JavaScript or map-related logic needed here as per user's request to remove map.
    }
}

/**
 * Hook function for checkout_post_customer_information.
 * Used to prepare data for the checkout view, specifically for Armi shipping map integration.
 *
 * @param string $mode The mode of the checkout controller.
 * @param array $cart The cart data.
 * @param array $auth User authentication data.
 * @param array $profile_fields User profile fields.
 */
function fn_armi_shipping_checkout_post_customer_information($mode, $cart, $auth, $profile_fields)
{
    if (AREA == 'C' && $mode == 'checkout') {
        // Check if Armi shipping might be available or is selected to decide if we load the map resources.
        // For now, let's assume it's always loaded if the addon is active,
        // and JS will handle showing/hiding the map based on selection.

        $google_maps_api_key = Registry::get('addons.armi_shipping.google_maps_api_key');

        if (!empty($google_maps_api_key)) {
            Tygh::$app['view']->assign('armi_google_maps_api_key', $google_maps_api_key);
            
            // Enqueue the checkout map JavaScript file
            Tygh::$app['view']->setScript('js/addons/armi_shipping/checkout_map.js', true);
        } else {
            fn_log_event('armi_shipping', 'configuration_error', ['message' => 'Google Maps API Key for Armi Shipping is not configured. Checkout map will not load.']);
        }
    }
}
?>
