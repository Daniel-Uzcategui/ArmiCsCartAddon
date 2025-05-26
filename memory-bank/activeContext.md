# Active Context: CS-Cart Armi Shipping Addon

## 1. Current Overall Focus
The project is currently in the initial phase of development. The primary focus is on establishing the foundational documentation (Memory Bank) and then moving into the structural definition of the CS-Cart addon, starting with `addon.xml`.

## 2. Recent Activities & Changes
*   Project initiated by user request.
*   Analyzed provided Postman collection (`AmryNode_API.postman_collection.json`) to understand Armi API endpoints, request/response structures, and authentication.
*   Reviewed `ARMI_STATUS.csv` for Armi order status codes.
*   Reviewed `Campos.pdf` for `vehicle_type` IDs, `payment_method` IDs, country codes, and further clarification on API payloads.
*   Key decisions made:
    *   Default Armi `payment_method` to `3` (Transacción en linea) for prepaid CS-Cart orders, with vendor override.
    *   Use custom database tables for vendor-specific Armi settings and for storing Armi-related order data.
*   Core Memory Bank files established:
    *   `projectbrief.md`
    *   `productContext.md`
    *   `systemPatterns.md`
    *   `techContext.md`
    *   `activeContext.md` (this file)
    *   `progress.md`
*   **Previously Addressed `IService` implementation**: Implemented placeholder methods in `app/Tygh/Shippings/Services/ArmiShipping.php` to satisfy the `IService` interface.
*   **Corrected `IService` Method Signatures (2025-05-23)**: Resolved a PHP Fatal Error ("Declaration of ... must be compatible with ...") in `app/Tygh/Shippings/Services/ArmiShipping.php` by correcting the method signatures for `getRequestData()` and `getSimpleRates()` to have no parameters, matching the `Tygh\Shippings\IService` interface definition.
*   **Made `getInfo()` Static (2025-05-23)**: Resolved a PHP Error ("Non-static method ... getInfo() cannot be called statically") by changing `getInfo()` in `app/Tygh/Shippings/Services/ArmiShipping.php` to be a `public static` method, as it's called statically by CS-Cart's core `Shippings::getCarrierInfo()`.
*   **Revised Settings Management Model (2025-05-23)**:
    *   Clarified that Armi API Key, Business ID, Branch Office ID, Country Code, and Origin Lat/Lng are to be configured by **vendors** for **each shipping method instance** they create, using the shipping method's "Configure" tab.
    *   These settings will be stored in `service_params` of the `?:shippings` table.
    *   The `cscart_armi_vendor_settings` table and the vendor company settings tab for these specific details are no longer needed.
    *   `ArmiShipping.php` updated: `getInfo()` is configurable, `getSettingsForm()` points to `armi_shipping_configure.tpl`.
    *   `armi_shipping_configure.tpl` updated with fields for these settings.
    *   `js/addons/armi_shipping/configure_map.js` created for the map on this tab.
    *   `init.php` updated to hook `shippings_configure_post` to load the new JS.
    *   `func.php` updated:
        *   Added `fn_armi_shipping_shippings_configure_post()` to enqueue JS.
        *   Added `fn_armi_shipping_api_request_with_params()` to use API key/country from params.
        *   Removed `fn_armi_shipping_get_vendor_settings`, `fn_armi_shipping_update_vendor_settings`, and old `fn_armi_shipping_api_request`.
        *   Removed hooks from `init.php` related to vendor company settings tab.
    *   `ArmiShipping::calculateRate()` updated to use `service_params` and call `fn_armi_shipping_api_request_with_params()`.
*   **Core Error Context**: The "Undefined variable $shipping" error in the core `shippings.php` is primarily an issue for administrators viewing the "Configure" tab. Since vendors are the primary users of this tab for setting up their shipping methods, this core error is less critical for the addon's main workflow but remains a known CS-Cart core behavior for admins.
*   **[2025-05-23] Fixed Empty Configure Tab**:
    *   Corrected the module check in `app/addons/armi_shipping/func.php` within `fn_armi_shipping_shippings_configure_post` from `if ($module == 'armi_logistics')` to `if ($module == 'armi_shipping')`.
    *   Added missing language variables (`armi_shipping.configuration_incomplete`, `armi_destination_not_set`, `cannot_determine_vendor`, `armi_shipping.map_loading`, `armi_shipping.map_interaction_hint_configure`) to `app/addons/armi_shipping/addon.xml`.
*   **[2025-05-23] Removed Google Maps Integration from Configure Tab**:
    *   Removed map-related HTML elements and comments from `app/addons/armi_shipping/views/shippings/components/services/armi_shipping_configure.tpl`.
    *   Removed JavaScript enqueueing and Google Maps API key assignment logic from `fn_armi_shipping_shippings_configure_post` in `app/addons/armi_shipping/func.php`. The tab will now rely solely on standard input fields for origin latitude and longitude.

## 3. Immediate Next Steps
1.  **Testing**: Thoroughly test the vendor workflow:
    *   Vendor creates a new shipping method, selects "Armi Delivery".
    *   Vendor uses the "Configure" tab to input their Armi API Key, Business ID, Branch Office ID, Country, Origin Lat/Lng using the plain input fields.
    *   Ensure these settings are saved correctly in `service_params`.
    *   Test shipping calculation at checkout using these `service_params`.
2.  **Review Core Files**:
    *   Thoroughly review `addon.xml` for completeness (settings, language variables, DB queries). (Already reviewed and updated language variables)
    *   Refine `func.php` and `init.php` stubs and implementations.
3.  **Language Files**:
    *   Create `app/addons/armi_shipping/var/langs/en/language_variables.po` (and other languages if needed). (Language variables added to addon.xml, but .po file generation/management is a separate step)
4.  **Hook Implementations (in `func.php` or new controllers)**:
    *   `fn_armi_shipping_orders_details_post_processor`
    *   `fn_armi_shipping_checkout_process_step_post`
    *   `fn_armi_shipping_calculate_cart_content_before_shipping`
    *   `fn_armi_shipping_checkout_shipping_rates_post`
5.  **Database Implementation**:
    *   Verify table creation in `fn_armi_shipping_install()` (Note: `addon.xml` handles this via `<queries>`).
    *   Implement table removal in `fn_armi_shipping_uninstall()`.
6.  **Settings Implementation**:
    *   Global addon settings (Google Maps API Key in `addon.xml`).
    *   Vendor-specific settings page/tab (controller, templates).
    *   Logic to save/retrieve vendor settings from `cscart_armi_vendor_settings`.
7.  **Shipping Calculation Logic**:
    *   Implement `shippings_calculate_rates` hook function.
    *   API call to Armi `order/delivery-cost`.
8.  **Checkout Integration**:
    *   Template modifications for Google Map display (if re-introduced later).
    *   JavaScript for lat/long capture and address field hiding (if map is re-introduced, or for plain input fields).
    *   Controller/AJAX logic for handling map data (if map is re-introduced).
9.  **Order Management (Vendor Panel)**:
    *   Template modifications for Armi actions and status display.
    *   Controller logic for "Create Armi Order" (including vehicle type selection, payment method logic, API call to `monitor/order/create`).
    *   Controller logic for "Cancel Armi Order" (API call to `monitor/order/cancel`).
    *   Logic to fetch and display Armi order status (API call to `monitor/order/status`).
    *   Saving/retrieving data from `cscart_armi_order_data`.
10. **API Client Implementation**:
    *   Develop robust helper class/functions for all Armi API interactions.
11. **Testing**:
    *   Unit testing (where applicable).
    *   Integration testing (checkout flow, vendor order management).
    *   User acceptance testing.
12. **Documentation**:
    *   User guide for administrators and vendors.
    *   Ongoing updates to the Memory Bank.

## 4. Active Decisions & Considerations
*   **Vendor Settings UI**: Decide whether to integrate into the existing company settings page or create a new, dedicated page for Armi vendor settings. A dedicated page might be cleaner.
*   **Armi API Base URL**: The Postman collection uses `https://armi-business-monitor-dot-armirene-369418.uc.r.appspot.com`. Consider if this should be a configurable setting in the addon (e.g., for testing against a staging Armi API, if one exists). For now, assume it's fixed.
*   **Error Handling Specificity**: Define how detailed API error messages should be logged versus displayed to users (vendors/customers).
*   **Mapping CS-Cart Payment Methods to Armi**: The logic for determining if a CS-Cart payment is "prepaid" to default Armi `payment_method` to `3` needs to be robust. This might involve checking `processor_params.is_offline == 'N'` or similar flags on the CS-Cart payment method.

## 5. Important Patterns & Preferences (Emerging)
*   **Documentation First**: Adherence to the Memory Bank system is critical.
*   **Custom Tables**: Preference for custom tables for addon-specific data (`cscart_armi_vendor_settings`, `cscart_armi_order_data`) for clarity and to avoid cluttering core CS-Cart tables.
*   **API Abstraction**: Encapsulate Armi API interactions within a helper class/functions.
*   **User-Centric Defaults**: Provide sensible defaults (e.g., Armi payment method based on CS-Cart order payment type) while allowing overrides.

## 6. Learnings & Project Insights (So Far)
*   The Armi API relies heavily on IDs (business, branch, vehicle, payment, status codes). Clear mapping and management of these IDs will be crucial.
*   The use of latitude/longitude for both origin and destination is a core aspect, making Google Maps integration vital.
*   The project requires modifications across various parts of CS-Cart: checkout, vendor panel (settings and order management), and admin panel (addon settings).
