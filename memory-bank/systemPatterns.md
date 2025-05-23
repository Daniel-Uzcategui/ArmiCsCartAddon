# System Patterns: CS-Cart Armi Shipping Addon

## 1. Core Architecture
The addon will follow CS-Cart's standard addon architecture, residing in `app/addons/armi_shipping/`. It will leverage CS-Cart's hook system, controllers, and schema definitions for integration.

## 2. Settings Management
*   **Global Addon Settings**:
    *   The Google Maps API Key will be a global setting defined in `addon.xml` and managed via the CS-Cart admin panel (Manage addons -> Armi Shipping -> Settings).
*   **Shipping Method Instance-Specific Settings (Managed by Vendors on "Configure" Tab)**:
    *   Armi API Key, Business ID, Branch Office ID (Origin), API Country Code, Origin Latitude, and Origin Longitude will be stored in the `service_params` field of the `?:shippings` table for each Armi shipping method instance created by a vendor.
    *   Vendors will configure these settings on the "Configure" tab of the shipping method they create/edit in their vendor panel.
    *   The `app/addons/armi_shipping/views/shippings/components/services/armi_shipping_configure.tpl` template will provide the form fields.
    *   JavaScript (`js/addons/armi_shipping/configure_map.js`) will handle the Google Maps integration for origin selection on this tab.

## 3. Shipping Calculation
*   **Hook**: The `shippings_calculate_rates` hook will be used to integrate the Armi shipping calculation.
*   **Process**:
    1.  Check if the Armi shipping method is active and applicable.
    2.  Retrieve customer's destination latitude/longitude (captured via Google Maps at checkout and stored temporarily, possibly in `Tygh::$app['session']['cart']['shippings_extra']['armi_destination']` or similar).
    3.  Retrieve the Armi credentials (API Key, Business ID, Country Code, Branch Office ID) and origin latitude/longitude from the active shipping method's `service_params` (`$shipping_info['service_params']` in `ArmiShipping::calculateRate`).
    4.  Construct the payload for the Armi `order/delivery-cost` API endpoint.
    5.  Make the API call using `Tygh\Http`.
    6.  Parse the API response. If successful, extract the delivery cost.
    7.  Return the rate to CS-Cart, associated with the Armi shipping service.
    8.  Handle API errors gracefully, potentially logging them and not offering the shipping method if calculation fails.

## 4. Checkout Integration (Google Maps for Destination)
*   **Template Modification**: Override relevant checkout templates (e.g., `checkout/components/shipping_rates.tpl` or location step templates) to include a placeholder for the Google Map.
*   **JavaScript Logic**:
    *   Load Google Maps API script (if not already loaded by another addon/theme).
    *   Initialize the map when Armi shipping is selected/active.
    *   Allow customer to place/drag a marker to specify their delivery location.
    *   Update hidden input fields with the selected latitude and longitude.
    *   These hidden fields will be submitted with the checkout form.
    *   An AJAX call might be made to a custom controller to store these coordinates in the session/cart data as the customer interacts with the map, ensuring they are available for the `shippings_calculate_rates` hook.
*   **Address Field Hiding**: JavaScript will be used to hide or disable standard CS-Cart address input fields (street, city, etc.) when the Armi shipping method is selected to avoid confusion, as Armi relies on coordinates.

## 5. Order Management (Vendor Panel)
*   **Hook/Template**: Modify the order details page in the vendor panel (e.g., using `orders_details_post` hook or overriding `orders/details.tpl`).
*   **Data Storage**: Armi-specific order data (Armi Order ID, last status, vehicle type, etc.) will be stored in the `cscart_armi_order_data` custom table, linked to the CS-Cart `order_id`.
*   **"Create Armi Order" Functionality**:
    *   A button/form will trigger a dispatch to a custom controller action.
    *   The controller will gather necessary data from the CS-Cart order and the shipping method's `service_params` (for Armi credentials).
    *   It will allow the vendor to select `vehicle_type`.
    *   It will determine the Armi `payment_method` (defaulting to `3` for prepaid CS-Cart orders, with vendor override).
    *   Call the Armi `monitor/order/create` API using credentials from `service_params`.
    *   On success, store the returned Armi Order ID and other relevant data in `cscart_armi_order_data`.
    *   Display success/failure notifications.
*   **"Cancel Armi Order" Functionality**:
    *   A button/link will trigger a dispatch to a custom controller action.
    *   The controller retrieves the Armi Order ID from `cscart_armi_order_data`.
    *   Call the Armi `monitor/order/cancel` API.
    *   Update local status if necessary and display notifications.
*   **Display Armi Order Status**:
    *   Fetch the Armi Order ID from `cscart_armi_order_data`.
    *   If an Armi Order ID exists, make a call to Armi `monitor/order/status/{armiOrderId}` API (or use a locally stored/webhook-updated status).
    *   Display the human-readable status (mapped from Armi status codes).

## 6. API Interaction
*   A dedicated helper class or set of functions within the addon (e.g., `Tygh\Addons\ArmiShipping\ApiHelper`) will encapsulate all Armi API calls.
*   This helper will manage:
    *   Constructing request payloads.
    *   Adding authentication headers (`armi-business-api-key`, `country`).
    *   Making HTTP requests using `Tygh\Http`.
    *   Parsing JSON responses.
    *   Basic error handling and logging for API communication.

## 7. Installation & Uninstallation (`func.php`)
*   **`fn_armi_shipping_install()`**:
    *   Create custom table `cscart_armi_order_data` using `db_query` (or `addon.xml`). The `cscart_armi_vendor_settings` table is no longer needed for these core credentials.
    *   Register the shipping carrier "Armi Logistics" and service "Armi Delivery" using `fn_update_shipping()`.
    *   Set up default global addon settings (e.g., Google Maps API key if not solely in `addon.xml`).
*   **`fn_armi_shipping_uninstall()`**:
    *   Remove the shipping service and carrier.
    *   Drop custom table `cscart_armi_order_data`.
    *   Remove global addon settings.

## 8. Error Handling and Logging
*   Utilize CS-Cart's logging mechanism (`fn_log_event`) for critical errors, especially API communication failures.
*   Provide user-friendly error messages in the UI where appropriate (e.g., "Could not calculate shipping cost at this time.").
