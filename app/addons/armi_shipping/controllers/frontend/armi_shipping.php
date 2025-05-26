<?php

use Tygh\Registry;
use Tygh\Ajax;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// Handle AJAX requests for the Armi Shipping addon
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Save customer's selected coordinates
    if ($mode == 'save_coordinates') {
        if (defined('AJAX_REQUEST')) {
            $latitude = isset($_REQUEST['latitude']) ? floatval($_REQUEST['latitude']) : null;
            $longitude = isset($_REQUEST['longitude']) ? floatval($_REQUEST['longitude']) : null;
            $shipping_id = isset($_REQUEST['shipping_id']) ? intval($_REQUEST['shipping_id']) : null;
            $group_key = isset($_REQUEST['group_key']) ? $_REQUEST['group_key'] : null; // group_key can be string or int

            if ($latitude !== null && $longitude !== null && $shipping_id !== null && $group_key !== null &&
                $latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {

                // Ensure the shippings_extra array exists
                if (!isset(Tygh::$app['session']['cart']['shippings_extra'])) {
                    Tygh::$app['session']['cart']['shippings_extra'] = [];
                }
                if (!isset(Tygh::$app['session']['cart']['shippings_extra']['armi_destination'])) {
                    Tygh::$app['session']['cart']['shippings_extra']['armi_destination'] = [];
                }
                if (!isset(Tygh::$app['session']['cart']['shippings_extra']['armi_destination'][$group_key])) {
                    Tygh::$app['session']['cart']['shippings_extra']['armi_destination'][$group_key] = [];
                }
                if (!isset(Tygh::$app['session']['cart']['shippings_extra']['armi_destination'][$group_key][$shipping_id])) {
                    Tygh::$app['session']['cart']['shippings_extra']['armi_destination'][$group_key][$shipping_id] = [];
                }

                Tygh::$app['session']['cart']['shippings_extra']['armi_destination'][$group_key][$shipping_id]['latitude'] = $latitude;
                Tygh::$app['session']['cart']['shippings_extra']['armi_destination'][$group_key][$shipping_id]['longitude'] = $longitude;
                
                // Mark cart as changed so shipping recalculation might be triggered if needed
                Tygh::$app['session']['cart']['calculate_shipping'] = true;


                Registry::get('ajax')->assign('status', 'ok');
                Registry::get('ajax')->assign('message', __('text_armi_coordinates_saved')); // Add this lang var
            } else {
                Registry::get('ajax')->assign('status', 'error');
                Registry::get('ajax')->assign('message', __('text_armi_invalid_coordinates')); // Add this lang var
            }
        } else {
            // Not an AJAX request, should not happen for this mode
            return [CONTROLLER_STATUS_NO_PAGE];
        }
        exit; // Important for AJAX handlers
    }
}

// If no specific mode is matched for POST, or if it's a GET request,
// typically you would redirect or show a 'no_page' status.
// For this controller, we only expect AJAX POSTs for now.
if ($mode !== 'save_coordinates' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
     return [CONTROLLER_STATUS_NO_PAGE];
}
