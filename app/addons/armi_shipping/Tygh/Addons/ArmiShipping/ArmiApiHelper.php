<?php

namespace Tygh\Addons\ArmiShipping;

use Tygh\Registry;
use Tygh\Http;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

class ArmiApiHelper
{
    /**
     * Makes a request to the Armi API.
     *
     * @param string $endpoint The API endpoint (e.g., 'order/delivery-cost'). Note: leading slash is optional.
     * @param array  $data The data to send in the request body.
     * @param string $method HTTP method ('GET', 'POST', 'DELETE', etc.).
     * @param string $api_key The Armi API key for the vendor.
     * @param string $country_code The country code for the API request (e.g., 'COL', 'VEN').
     * @return array|false The decoded JSON response or false on failure.
     */
    public static function request($endpoint, $data = [], $method = 'POST', $api_key = '', $country_code = '')
    {
        $api_base_url = Registry::get('addons.armi_shipping.armi_api_base_url');
        if (empty($api_base_url)) {
            fn_log_event('armi_shipping', 'api_error', [
                'type' => 'Configuration',
                'message' => 'Armi API Base URL is not configured in addon settings.'
            ]);
            return false;
        }

        if (empty($api_key)) {
            fn_log_event('armi_shipping', 'api_error', [
                'type' => 'Authentication',
                'message' => 'Armi API key not provided for API request.',
                'endpoint' => $endpoint
            ]);
            return false;
        }

        if (empty($country_code)) {
            fn_log_event('armi_shipping', 'api_error', [
                'type' => 'Authentication',
                'message' => 'Armi API country code not provided for API request.',
                'endpoint' => $endpoint
            ]);
            return false;
        }

        $url = rtrim($api_base_url, '/') . '/' . ltrim($endpoint, '/');
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'armi-business-api-key: ' . $api_key,
            'country: ' . $country_code,
        ];

        $request_data_json = !empty($data) ? json_encode($data) : '';
        if (json_last_error() !== JSON_ERROR_NONE) {
            fn_log_event('armi_shipping', 'api_error', [
                'type' => 'Request Preparation',
                'message' => 'Failed to encode request data to JSON.',
                'endpoint' => $endpoint,
                'data' => $data // Log original data for debugging
            ]);
            return false;
        }

        fn_log_event('armi_shipping', 'api_request', [ // Log the request being made
            'endpoint' => $endpoint,
            'method' => $method,
            'url' => $url,
            'request_data' => $data // Log decoded data for readability
        ]);

        $response_raw = Http::request($method, $url, $request_data_json, [
            'headers' => implode("\r\n", $headers),
            'timeout' => 30, // Increased timeout for potentially longer operations
        ]);

        $http_status = Http::getStatus();

        if (empty($response_raw)) {
            fn_log_event('armi_shipping', 'api_error', [
                'type' => 'Communication',
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $http_status,
                'message' => 'Empty response from Armi API.',
            ]);
            return false;
        }

        $decoded_response = json_decode($response_raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            fn_log_event('armi_shipping', 'api_error', [
                'type' => 'Response Processing',
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $http_status,
                'message' => 'Failed to decode JSON response from Armi API.',
                'response_raw' => mb_substr($response_raw, 0, 1000) // Log a snippet of raw response
            ]);
            return false;
        }
        
        fn_log_event('armi_shipping', 'api_response', [ // Log the response
            'endpoint' => $endpoint,
            'method' => $method,
            'status' => $http_status,
            'response' => $decoded_response
        ]);

        // Consider Armi API's own success/error structure if it has one beyond HTTP status
        // For now, we assume 2xx is success.
        if ($http_status < 200 || $http_status >= 300) {
             fn_log_event('armi_shipping', 'api_error', [
                'type' => 'API Error Status',
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $http_status,
                'message' => 'Armi API returned a non-successful HTTP status.',
                'response' => $decoded_response,
            ]);
            // Optionally, you could return the decoded response here if it contains error details
            // that the calling function can use to provide user-friendly messages.
            // For now, returning false indicates a general failure.
            return ['_error' => true, 'http_status' => $http_status, 'response' => $decoded_response];
        }

        return $decoded_response;
    }

    /**
     * Get delivery cost from Armi.
     *
     * @param array $params Parameters for the delivery cost API. Expected keys:
     *                      'origin_lat', 'origin_lng', 'destination_lat', 'destination_lng',
     *                      'country_code_origin', 'city_code_origin', (and others as per Armi docs)
     * @param string $api_key Vendor's Armi API key.
     * @param string $country_code Vendor's Armi country code for the API header.
     * @return array|false Decoded API response or false on failure.
     */
    public static function getDeliveryCost($params, $api_key, $country_code)
    {
        // Endpoint from techContext.md: /order/delivery-cost
        return self::request('order/delivery-cost', $params, 'POST', $api_key, $country_code);
    }

    /**
     * Create an order in Armi.
     *
     * @param array $order_payload Payload for the Armi order creation API.
     * @param string $api_key Vendor's Armi API key.
     * @param string $country_code Vendor's Armi country code for the API header.
     * @return array|false Decoded API response or false on failure.
     */
    public static function createOrder($order_payload, $api_key, $country_code)
    {
        // Endpoint from techContext.md: /monitor/order/create
        return self::request('monitor/order/create', $order_payload, 'POST', $api_key, $country_code);
    }

    /**
     * Cancel an order in Armi.
     *
     * @param string $armi_order_id The Armi Order ID to cancel.
     * @param string $reason Cancellation reason.
     * @param string $api_key Vendor's Armi API key.
     * @param string $country_code Vendor's Armi country code for the API header.
     * @return array|false Decoded API response or false on failure.
     */
    public static function cancelOrder($armi_order_id, $reason, $api_key, $country_code)
    {
        // Endpoint from techContext.md: /monitor/order/cancel
        $payload = [
            'orderId' => $armi_order_id,
            'reason' => $reason,
        ];
        return self::request('monitor/order/cancel', $payload, 'POST', $api_key, $country_code);
    }

    /**
     * Get the status of an Armi order.
     *
     * @param string $armi_order_id The Armi Order ID.
     * @param string $api_key Vendor's Armi API key.
     * @param string $country_code Vendor's Armi country code for the API header.
     * @return array|false Decoded API response or false on failure.
     */
    public static function getOrderStatus($armi_order_id, $api_key, $country_code)
    {
        // Endpoint from techContext.md: /monitor/order/status/{orderId}
        return self::request('monitor/order/status/' . $armi_order_id, [], 'GET', $api_key, $country_code);
    }
}
