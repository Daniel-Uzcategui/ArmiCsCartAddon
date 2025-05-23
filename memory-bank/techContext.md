# Technical Context: CS-Cart Armi Shipping Addon

## 1. Platform & Environment
*   **CS-Cart Version**: Multi-Vendor Ultimate 4.18.x
*   **Server Environment**: Standard CS-Cart hosting requirements (PHP, MySQL/MariaDB, Apache/Nginx).
*   **Languages**:
    *   PHP (primarily for backend logic, API interactions, hooks)
    *   Smarty (CS-Cart's template engine, for frontend modifications)
    *   JavaScript (for Google Maps integration, AJAX calls, dynamic form behavior)
    *   SQL (for custom table definitions and queries)
*   **CS-Cart Addon Structure**: Adherence to the standard CS-Cart addon directory structure and conventions (e.g., `app/addons/armi_shipping/`, `addon.xml`, `func.php`, `init.php`, controllers, schemas, template overrides).

## 2. APIs and External Services
*   **Armi API**:
    *   **Base URL**: `https://armi-business-monitor-dot-armirene-369418.uc.r.appspot.com` (from Postman collection, may need to be configurable if there are different environments like staging/production).
    *   **Authentication**: Requires `armi-business-api-key` and `country` (e.g., 'COL', 'VEN') in request headers for most endpoints.
    *   **Key Endpoints**:
        *   `POST /order/delivery-cost`: To calculate shipping costs.
            *   Inputs: origin lat/long, destination lat/long, country, city, vehicle, weight, etc.
        *   `POST /monitor/order/create`: To create an order in Armi's system.
            *   Inputs: business_id, total_value, delivery_value, vehicle_type, payment_method, products, client_info (including lat/long), country, etc.
        *   `POST /monitor/order/cancel`: To cancel an existing Armi order.
            *   Inputs: orderId, reason.
        *   `GET /monitor/order/status/{orderId}`: To get the current status of an Armi order.
    *   **Data Format**: JSON for requests and responses.
*   **Google Maps JavaScript API**:
    *   Used for displaying interactive maps at checkout (customer delivery location) and in vendor settings (vendor origin location).
    *   Requires an API Key (provided: `AIzaSyBALQyZf54Qvxz9xFkEUXnvQXrRp9P8GXI`, to be stored as a global addon setting).
    *   Key functionalities: Map initialization, marker placement/dragging, geocoding (if needed, though primary input is lat/long), "Locate Me" (geolocation).

## 3. Armi API Enumerations & Statuses
*   **Vehicle Types (`vehicle_type` for Armi API)**:
    *   `1`: Bicicleta (Bicycle)
    *   `2`: Motocicleta (Motorcycle)
    *   `3`: Carro (Car)
*   **Payment Methods (`payment_method` for Armi API - Reference from `Campos.pdf`)**:
    *   `1`: Efectivo
    *   `2`: Datafono
    *   `3`: Transacción en linea
    *   `4`: Pago en tienda
    *   `5`: Seguros Bolivar
    *   `6`: PSE
    *   `7`: Rappi Payless
    *   *(Note: Addon will default to `3` for prepaid CS-Cart orders, with vendor override capability).*
*   **Country Codes (`country` for Armi API)**:
    *   `COL`: Colombia
    *   `VEN`: Venezuela
*   **Armi Order Statuses (Codes and Descriptions from `ARMI_STATUS.csv` & `Campos.pdf`)**:
    *   `0`: RECIBIDA
    *   `1`: EMITIDA
    *   `2`: ENVIADA
    *   `3`: ASIGNADA
    *   `4`: PICKING
    *   `5`: FACTURADA
    *   `6`: ENTREGADA
    *   `7`: FINALIZADA
    *   `8`: OCULTA
    *   `9`: PREPROCESADO
    *   `10`: MODIFICADA
    *   `11`: ENVIADA CON ERROR
    *   `12`: PAGADA
    *   `13`: EN COLA POR PAGAR
    *   `14`: CANCELADA
    *   ... (and all other statuses as per the provided CSV/PDF) ...
    *   `49`: DEVOLUCION EXITOSA

## 4. Data Storage
*   **Shipping Method Instance Settings (`?:shippings.service_params`)**:
    *   Armi API Key, Business ID, Branch Office ID (Origin), API Country Code, Origin Latitude, and Origin Longitude are stored as serialized data within the `service_params` field of the `?:shippings` table. Each vendor configures these for their Armi shipping method instance via the "Configure" tab.
*   **`cscart_armi_order_data`** (Stores Armi-related info for each CS-Cart order, Table DEFAULT CHARSET=utf8mb3)
    *   `order_id` (MEDIUMINT UNSIGNED, Primary Key, Foreign Key to `?:orders.order_id`)
    *   `armi_order_id` (VARCHAR(255), NULLABLE)
    *   `armi_last_status_code` (INT, NULLABLE)
    *   `armi_vehicle_type_id` (INT, NULLABLE)
    *   `armi_payment_method_id` (INT, NULLABLE)
    *   `armi_calculated_delivery_cost` (DECIMAL(12, 2), NULLABLE)
    *   `customer_destination_latitude` (DECIMAL(10, 8), NULLABLE)
    *   `customer_destination_longitude` (DECIMAL(11, 8), NULLABLE)

## 5. Key Technical Decisions & Considerations
*   **API Client**: Use CS-Cart's `Tygh\Http` class for making API calls to Armi. Implement robust error handling and logging for API interactions.
*   **Security**: API keys (Google Maps, Armi vendor keys) should be stored securely. Avoid exposing sensitive information on the client-side.
*   **Performance**: Minimize API calls. Cache data where appropriate (e.g., vendor settings). Optimize database queries.
*   **Modularity**: Keep Armi-specific logic well-encapsulated within the addon.
*   **CS-Cart Upgrades**: Aim for compatibility with future CS-Cart minor versions by using official APIs and hook points where possible.
*   **Internationalization/Localization**: Addon text strings should be managed via CS-Cart language variables (`.po` files) for potential translation.
