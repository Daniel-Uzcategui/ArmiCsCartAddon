# Progress: CS-Cart Armi Shipping Addon

## 1. Current Overall Status
*   **Phase**: Development (Core functionality & Bug Fixing)
*   **Date**: 2025-05-23
*   **Summary**: Foundational Memory Bank and core addon files are in place. Addressed a critical PHP fatal error in the shipping service class. Focus is shifting towards implementing core features like vendor settings and shipping calculation.

## 2. What Works / Completed
*   **Information Gathering**:
    *   Analysis of Armi API Postman collection.
    *   Review of `ARMI_STATUS.csv` for order statuses.
    *   Review of `Campos.pdf` for vehicle types, payment methods, country codes, and API payload details.
*   **Initial Planning**:
    *   High-level feature list defined.
    *   User experience goals outlined.
    *   Technical requirements and constraints identified.
    *   System patterns for core functionalities (settings, shipping calculation, checkout, order management) drafted.
    *   Preliminary database schema for custom tables designed.
*   **Memory Bank Setup**:
    *   `memory-bank/projectbrief.md` created.
    *   `memory-bank/productContext.md` created.
    *   `memory-bank/techContext.md` created.
    *   `memory-bank/systemPatterns.md` created.
    *   `memory-bank/activeContext.md` created.
    *   `memory-bank/progress.md` (this file) created.
*   **Core Addon Files Created**:
    *   `app/addons/armi_shipping/addon.xml` (defines addon metadata, settings, DB queries, language vars).
    *   `app/addons/armi_shipping/func.php` (contains install/uninstall, settings helpers, API request function).
    *   `app/addons/armi_shipping/init.php` (registers necessary hooks).
    *   `app/Tygh/Shippings/Services/ArmiShipping.php` (implements CS-Cart's IService for rate calculation).
*   **Bug Fixes**:
    *   Initial implementation of `IService` methods in `app/Tygh/Shippings/Services/ArmiShipping.php` to resolve missing method errors.
    *   **[2025-05-23]** Corrected `IService` method signatures in `app/Tygh/Shippings/Services/ArmiShipping.php` for `getRequestData()` and `getSimpleRates()` to have no parameters, matching the `Tygh\Shippings\IService` interface, resolving a "must be compatible" fatal error.
    *   **[2025-05-23]** Changed `getInfo()` method in `app/Tygh/Shippings/Services/ArmiShipping.php` to `public static` to resolve a "Non-static method ... cannot be called statically" error, aligning with CS-Cart core usage.
    *   **[2025-05-23]** Revised Settings Management Model:
        *   Confirmed Armi credentials (API Key, Business ID, etc.) are vendor-managed per shipping method instance via the "Configure" tab (`service_params`).
        *   `ArmiShipping.php` updated for this: `getInfo()` is configurable, `getSettingsForm()` points to `armi_shipping_configure.tpl`.
        *   `armi_shipping_configure.tpl` updated with form fields for these instance settings.
        *   `js/addons/armi_shipping/configure_map.js` created for this tab's map.
        *   `init.php` updated to hook `shippings_configure_post` for the new JS.
        *   `func.php` updated:
            *   Added `fn_armi_shipping_shippings_configure_post()` to load JS.
            *   Added `fn_armi_shipping_api_request_with_params()` to use API key/country from `service_params`.
            *   Removed functions and hooks related to `cscart_armi_vendor_settings` table and vendor company settings tab for these credentials.
        *   `ArmiShipping::calculateRate()` updated to use `service_params` and the new API request function.

## 3. What's Left to Build / In Progress / Next Steps
*   **Language Variables**: Add new language variables for `armi_shipping_configure.tpl` and `configure_map.js`.
*   **Testing**: Test vendor workflow for configuring shipping methods and checkout calculation.
*   **Review `addon.xml`**: Ensure it reflects the removal of `cscart_armi_vendor_settings` if applicable and includes global settings.
*   **Review Core Files**:
    *   Thoroughly review `addon.xml` for completeness (settings, language variables, DB queries).
    *   Refine `func.php` and `init.php` stubs and implementations.
*   **Language Files**:
    *   Create `app/addons/armi_shipping/var/langs/en/language_variables.po` (and other languages if needed).
*   **Hook Implementations (in `func.php` or new controllers)**:
    *   `fn_armi_shipping_companies_tabs`
    *   `fn_armi_shipping_companies_tab_content`
    *   `fn_armi_shipping_update_company_post`
    *   `fn_armi_shipping_orders_details_post_processor`
    *   `fn_armi_shipping_checkout_process_step_post`
    *   `fn_armi_shipping_calculate_cart_content_before_shipping`
    *   `fn_armi_shipping_checkout_shipping_rates_post`
*   **Database Implementation**:
    *   Verify table creation in `fn_armi_shipping_install()` (Note: `addon.xml` handles this via `<queries>`).
    *   Implement table removal in `fn_armi_shipping_uninstall()`.
*   **Settings Implementation**:
    *   Global addon settings (Google Maps API Key in `addon.xml`).
    *   Vendor-specific settings page/tab (controller, templates, JavaScript for map).
    *   Logic to save/retrieve vendor settings from `cscart_armi_vendor_settings`.
*   **Shipping Calculation Logic**:
    *   Implement `shippings_calculate_rates` hook function.
    *   API call to Armi `order/delivery-cost`.
*   **Checkout Integration**:
    *   Template modifications for Google Map display.
    *   JavaScript for map interaction, lat/long capture, and address field hiding.
    *   Controller/AJAX logic for handling map data.
*   **Order Management (Vendor Panel)**:
    *   Template modifications for Armi actions and status display.
    *   Controller logic for "Create Armi Order" (including vehicle type selection, payment method logic, API call to `monitor/order/create`).
    *   Controller logic for "Cancel Armi Order" (API call to `monitor/order/cancel`).
    *   Logic to fetch and display Armi order status (API call to `monitor/order/status`).
    *   Saving/retrieving data from `cscart_armi_order_data`.
*   **API Client Implementation**:
    *   Develop robust helper class/functions for all Armi API interactions.
*   **Testing**:
    *   Unit testing (where applicable).
    *   Integration testing (checkout flow, vendor order management).
    *   User acceptance testing.
*   **Documentation**:
    *   User guide for administrators and vendors.
    *   Ongoing updates to the Memory Bank.

## 4. Known Issues & Blockers
*   PHP errors related to `ArmiShipping.php` method definitions (believed to be resolved).
*   "Undefined variable $shipping" in core `shippings.php` is a known CS-Cart behavior for admins but should not affect vendors configuring their shipping methods.
*   No other major blockers identified currently.

## 5. Evolution of Project Decisions & Key Milestones
*   **[2025-05-23]**: Project inception. Initial requirements gathered.
*   **[2025-05-23]**: Initial implementation of `IService` methods in `ArmiShipping.php`.
*   **[2025-05-23]**: Corrected method signatures for `getRequestData()` and `getSimpleRates()` in `ArmiShipping.php` to align with `IService` interface.
*   **[2025-05-23]**: Modified `getInfo()` in `ArmiShipping.php` to be `static` to align with CS-Cart core usage.
*   **[2025-05-23]**: Shifted Armi credentials (API Key, Business ID, etc.) from vendor-specific table to shipping method instance `service_params`, configured by vendors on the "Configure" tab. This involved updates to `ArmiShipping.php`, `armi_shipping_configure.tpl`, `func.php`, `init.php`, and creation of `js/addons/armi_shipping/configure_map.js`. Removed related vendor company settings UI and table.
*   **[2025-05-23]**: Decision to use custom tables for vendor settings and Armi order data.
    *   **[2025-05-23]**: Decision on default Armi payment method for prepaid CS-Cart orders.
    *   **[2025-05-23]**: Memory Bank foundational files created.
        *   *Next Milestone*: `addon.xml` and basic addon file structure created. (DONE)
        *   *Next Milestone*: Core PHP files (`func.php`, `init.php`, Service class) created. (DONE)
        *   *Next Milestone*: Vendor settings UI and basic JS map integration created (`armi_settings_tab.tpl`, `vendor_settings.js`, related `func.php` hooks). (DONE)
        *   *Next Milestone*: Database tables verified via `addon.xml` install (pending testing).
        *   *Next Milestone*: Vendor settings page fully functional (saving, map interaction robust).
        *   *Next Milestone*: Shipping calculation working at checkout with Google Maps.
        *   *Next Milestone*: Vendor order management (create/cancel/status) functional.
*   **[2025-05-23] Fixed Empty Configure Tab**:
    *   Corrected the module check in `app/addons/armi_shipping/func.php` within `fn_armi_shipping_shippings_configure_post` from `if ($module == 'armi_logistics')` to `if ($module == 'armi_shipping')`.
    *   Added missing language variables (`armi_shipping.configuration_incomplete`, `armi_destination_not_set`, `cannot_determine_vendor`, `armi_shipping.map_loading`, `armi_shipping.map_interaction_hint_configure`) to `app/addons/armi_shipping/addon.xml`.
