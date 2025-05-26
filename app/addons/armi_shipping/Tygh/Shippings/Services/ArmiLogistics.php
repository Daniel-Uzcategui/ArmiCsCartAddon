<?php

namespace Tygh\Shippings\Services;

use Tygh\Shippings\IService;
use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Shipping service for Armi Delivery.
 * Calculates rates based on Armi API using latitude and longitude.
 */
class ArmiLogistics implements IService
{
    const SHIPPING_CODE = 'armi_delivery';

    /**
     * @inheritDoc
     */
    public function prepareData($shipping_info)
    {
        // This method can be used to prepare any data needed by the service.
        // For Armi, we might not need much preparation here as data comes from cart & settings.
        return true;
    }

    /**
     * @inheritDoc
     */
    public function calculateRate($package_info, $shipping_info, &$error_message)
    {
        $rates = [];
        $armi_error_lang_key = 'armi_error_calculating_shipping'; // From addon.xml

        // Get customer's destination lat/lng
        // This needs to be reliably fetched from where we store it during checkout (e.g., session, cart user_data)
        $destination_location = !empty(Tygh::$app['session']['cart']['shippings_extra']['armi_destination'])
            ? Tygh::$app['session']['cart']['shippings_extra']['armi_destination']
            : null;

        if (empty($destination_location) || empty($destination_location['lat']) || empty($destination_location['lng'])) {
            $error_message = __('armi_destination_not_set'); // Add this lang var
            fn_log_event('armi_shipping', 'calculation_error', ['message' => 'Destination lat/lng not found in session for Armi shipping.']);
            return []; // No rates if destination is not set
        }
        
        $company_id = 0;
        if (!empty($package_info['vendor_id'])) { // CS-Cart MVE/Ultimate uses 'vendor_id' in package_info
            $company_id = $package_info['vendor_id'];
        } elseif (!empty($package_info['company_id'])) { // Fallback if structure changes or for single store context
             $company_id = $package_info['company_id'];
        }
        
        if (empty($company_id) && fn_allowed_for('ULTIMATE')) {
             // In Ultimate, shipping belongs to a vendor. If no company_id, something is wrong.
            $error_message = __('cannot_determine_vendor'); // Add this lang var
            fn_log_event('armi_shipping', 'calculation_error', ['message' => 'Cannot determine vendor for Armi shipping calculation.']);
            return [];
        } elseif (empty($company_id) && !fn_allowed_for('ULTIMATE')) {
            // For non-marketplace editions, company_id might be 0 (root admin/storefront owner)
            // We need to decide how Armi settings are handled in this case.
            // For now, assume it's primarily for MVE/Ultimate. If not, this logic needs adjustment.
            // Or, the root admin configures "default" Armi settings.
            // Let's assume for now that if company_id is 0, we try to use root admin's settings if they exist.
            // This part needs more thought if the addon is also for non-MVE.
            // Given "Multi-Vendor Ultimate" requirement, this path might not be hit often.
        }

        // Settings are now per shipping method instance, stored in service_params
        $service_params = $shipping_info['service_params'];

        if (empty($service_params['armi_api_key']) ||
            empty($service_params['armi_business_id']) ||
            empty($service_params['armi_country_code']) ||
            empty($service_params['armi_branch_office_id']) || // Added check for branch_office_id
            empty($service_params['origin_latitude']) ||
            empty($service_params['origin_longitude'])) {
            
            $error_message = __('armi_shipping.configuration_incomplete'); // Needs lang var
            fn_log_event('armi_shipping', 'calculation_error', [
                'message' => "Armi shipping method (ID: {$shipping_info['shipping_id']}) configuration is incomplete. Missing one or more required service_params.",
                'service_params' => $service_params,
                'company_id' => $company_id // Log company_id for context, even if settings are not from cscart_armi_vendor_settings
            ]);
            return [];
        }

        // Prepare data for Armi API /order/delivery-cost
        $request_payload = [
            'longitudeOrigin'       => (string) $service_params['origin_longitude'],
            'latitudeOrigin'        => (string) $service_params['origin_latitude'],
            'longitudeDestination'  => (string) $destination_location['lng'],
            'latitudeDestination'   => (string) $destination_location['lat'],
            'country'               => $service_params['armi_country_code'],
            'city'                  => !empty($package_info['location']['city']) ? $package_info['location']['city'] : '',
            'vehicle'               => 'MOTO', // Default vehicle, or make this configurable in service_params?
            'weight'                => !empty($package_info['W']) ? floatval($package_info['W']) : 1.0,
            'volume'                => 0.0,
            'subtotal'              => !empty($package_info['C']) ? floatval($package_info['C']) : 0.0,
            'branchOfficeId'        => (int) $service_params['armi_branch_office_id'],
        ];
        
        // Remove empty optional fields to keep payload clean
        foreach (['city', 'volume', 'subtotal'] as $optional_key) {
            if (empty($request_payload[$optional_key])) {
                unset($request_payload[$optional_key]);
            }
        }
        if (empty($request_payload['weight'])) $request_payload['weight'] = 1.0; // Ensure weight is present

        // The fn_armi_shipping_api_request function needs to be adapted or a new one created
        // if API key and country are now coming from service_params instead of vendor_settings.
        // For now, let's assume fn_armi_shipping_api_request will be adapted or we pass params directly.
        // We need to pass the API key and country to the API request function.
        $api_key = $service_params['armi_api_key'];
        $country_code = $service_params['armi_country_code']; // This is already in $request_payload

        // We need a way to make the API call using these specific credentials.
        // Modifying fn_armi_shipping_api_request or creating a new helper is an option.
        // For now, let's assume fn_armi_shipping_api_request can take these directly if company_id is null.
        // This part of fn_armi_shipping_api_request needs review.
        // Let's pass them explicitly for clarity for now.
        $api_response = fn_armi_shipping_api_request_with_params(
            '/order/delivery-cost', 
            $request_payload, 
            'POST', 
            $api_key, 
            $country_code // country is already in payload, but api_request might use it for header
        );

        if ($api_response === false || !isset($api_response['total'])) {
            $error_message = __($armi_error_lang_key);
            fn_log_event('armi_shipping', 'api_error_delivery_cost', [
                'message' => 'Failed to get delivery cost from Armi API or response format unexpected.',
                'shipping_id' => $shipping_info['shipping_id'],
                'payload' => $request_payload,
                'response' => $api_response
            ]);
            return [];
        }

        $cost = floatval($api_response['total']);

        // Store the calculated cost and destination for later use (e.g. order placement)
        $armi_order_data_temp = [
            'armi_calculated_delivery_cost' => $cost,
            'customer_destination_latitude' => $destination_location['lat'],
            'customer_destination_longitude' => $destination_location['lng'],
        ];
        Tygh::$app['session']['cart']['shippings_extra']['armi_order_data_temp'] = $armi_order_data_temp;


        $rates[] = [
            'cost' => $cost,
            'delivery_time' => '', // Armi API might provide this, if so, map it.
            'description' => __('shipping_armi_delivery'), // Language variable for "Armi Delivery"
        ];

        return $rates;
    }

    /**
     * @inheritDoc
     */
    public static function getInfo()
    {
        return [
            'name' => __('carrier_armi_logistics'), // Change this to the carrier name
            'description' => __('armi_shipping_description_admin'), // Admin description
            'carrier' => 'armi_logistics', // The code of the carrier this service belongs to
            // 'configurable' => true is implied if getSettingsForm returns a template path
        ];
    }

    /**
     * @inheritDoc
     */
    public function isCalculateButtonNeeded($shipping_settings, $cart_products, $cart, $auth)
    {
        // If we need manual recalculation (e.g., after map interaction), return true.
        // For now, assume rates are calculated when shipping step loads or AJAX updates.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getSettingsForm($shipping)
    {
        // Path to the template for the shipping method's configuration tab.
        // $shipping variable contains the current shipping method data.
        // Tygh::$app['view']->assign('armi_shipping_service_params', $shipping['service_params']); // Example if needed
        return 'addons/armi_shipping/views/shippings/components/services/armi_shipping_configure.tpl';
    }

    /**
     * @inheritDoc
     */
    public function processSettings($settings_data = [])
    {
        // No specific settings to process at the shipping method level.
        return $settings_data;
    }

    /**
     * @inheritDoc
     */
    public function getPickupPoints($package_info, $shipping_info, $response)
    {
        // Not a pickup point based shipping method.
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getPickupPoint($package_info, $shipping_info, $response, $pickup_id)
    {
        // Not a pickup point based shipping method.
        return [];
    }

    /**
     * @inheritDoc
     */
    public function allowMultithreading()
    {
        // Allow calculations to be performed in parallel with other shipping services.
        return true;
    }

    /**
     * @inheritDoc
     */
    public function processResponse($response)
    {
        // This method can be used to process the raw response from the shipping carrier.
        // For Armi, calculateRate already processes it to extract the cost.
        // If further processing or standardization of the response structure is needed,
        // it would be done here. For now, just return it.
        return $response;
    }

    /**
     * @inheritDoc
     */
    public function processErrors($response)
    {
        // This method is typically used to extract and format error messages from the
        // shipping carrier's response if the main calculation method didn't already
        // handle it or if a more generic error display mechanism relies on it.
        // In our calculateRate, we set $error_message directly.
        // If $response is a string (error message), return it. Otherwise, parse as needed.
        if (is_string($response)) {
            return $response;
        }
        
        // If $response is an array and contains an error message from Armi, extract it.
        // This depends on how Armi API returns errors in its JSON response.
        // For example, if Armi returns { "error": "message" }
        // if (isset($response['error'])) {
        //     return $response['error'];
        // }

        // For now, returning an empty string as errors are handled in calculateRate.
        return '';
    }

    /**
     * @inheritDoc
     *
     * Prepares data to be sent to the shipping carrier's server.
     * This method is usually called before making an API request if the request
     * needs specific formatting or data aggregation not handled by calculateRate directly.
     * For Armi, calculateRate currently constructs its own payload.
     * If a more centralized request preparation is needed, this method would be used.
     */
    public function getRequestData()
    {
        // Example: You might construct a standard request object/array here.
        // $request_data = [
        // 'shipping_settings' => $shipping_info['service_params'],
        // 'package_details' => $package_info,
        // 'auth_details' => $auth,
        // ];
        // return $request_data;

        // For now, returning an empty array as calculateRate handles its own data.
        return [];
    }

    /**
     * @inheritDoc
     *
     * Gets simple rates without making a request to the shipping carrier's server.
     * This is often used for very basic shipping methods or when rates are predefined
     * or can be calculated locally without external API calls.
     * For Armi, which relies on an external API for dynamic rate calculation,
     * this method is unlikely to provide actual rates.
     */
    public function getSimpleRates()
    {
        // Armi shipping requires an API call, so no simple rates are available.
        return [];
    }
}
