<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// Register hooks

// Hook for calculating shipping rates
// The actual calculation function will be in a separate file or func.php
// For CS-Cart, shipping modules often have a class in app/Tygh/Shippings/Services/
// and the 'module' field in ?:shipping_services points to this.
// If 'armi_shipping' is the module name, CS-Cart will look for app/Tygh/Shippings/Services/ArmiShipping.php
// and expect it to implement Tygh\Shippings\IService.
// Let's plan to create this service class.

// Hook for modifying the order details page (vendor panel)
fn_register_hooks(
    'orders_details_post_processor', // This hook allows modifying data sent to template or adding new tabs/blocks
    'fn_armi_shipping_orders_details_post_processor'
);

// Hook for checkout steps to potentially store lat/lng
fn_register_hooks(
    'checkout_process_step_post',
    'fn_armi_shipping_checkout_process_step_post'
);

// Hook to add custom data to cart that might be needed for shipping calculation
fn_register_hooks(
    'calculate_cart_content_before_shipping',
    'fn_armi_shipping_calculate_cart_content_before_shipping'
);

// Hook to modify shipping rates output, if needed to inject map or hide address fields
fn_register_hooks(
    'checkout_shipping_rates_post',
    'fn_armi_shipping_checkout_shipping_rates_post'
);

// Hook to add JS for the shipping method configuration tab (vendor panel)
fn_register_hooks(
    'shippings_configure_post', // This hook runs after the main logic of shippings.configure
    'fn_armi_shipping_shippings_configure_post'
);

?>
