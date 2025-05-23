# Product Context: CS-Cart Armi Shipping Addon

## 1. Problem Solved
This addon addresses the need for CS-Cart stores, particularly those using the Armi delivery service, to have an accurate and dynamic way to calculate delivery costs. Traditional address-based shipping calculation is insufficient for Armi, which relies on precise geographic latitude and longitude coordinates for its logistics. This addon bridges that gap.

It also streamlines order fulfillment for vendors using Armi by integrating Armi's order creation and cancellation processes directly into the CS-Cart vendor panel.

## 2. User Experience Goals
*   **For Customers (at Checkout)**:
    *   A seamless and intuitive experience when selecting Armi shipping.
    *   Easy and accurate pinpointing of their delivery location using an integrated Google Map.
    *   Clear presentation of the calculated delivery cost.
    *   Reduced confusion by hiding irrelevant standard address fields when Armi shipping is active.
*   **For Store Administrators**:
    *   Simple installation and initial configuration of the addon (e.g., setting the global Google Maps API key).
    *   Clear instructions and interface for managing addon settings.
*   **For Vendors**:
    *   Straightforward configuration of their specific Armi credentials and origin location (using a map interface).
    *   Efficient management of Armi orders directly from the CS-Cart order details page (create in Armi, cancel in Armi, view Armi status).
    *   Confidence in the accuracy of delivery costs provided to customers.

## 3. How it Should Work (High-Level Flow)
1.  **Installation & Global Setup**: The store administrator installs the addon. They configure the global Google Maps API key in the addon settings. The addon automatically registers the "Armi Logistics" carrier and "Armi Delivery" shipping method.
2.  **Vendor Configuration**: Each vendor navigates to a dedicated settings area (or their company settings page if extended) to input their Armi API Key, Business ID, Branch Office ID, and API Country Code. They also use an integrated Google Map to pinpoint their store's origin latitude and longitude for shipping calculations.
3.  **Customer Checkout**:
    *   If Armi Delivery is available and selected by the customer as their shipping method:
        *   The standard CS-Cart address fields (street, city, etc.) are hidden or de-emphasized.
        *   An interactive Google Map is displayed, allowing the customer to place a marker at their exact delivery location.
        *   The latitude and longitude from the map marker are captured.
    *   The addon uses the vendor's origin coordinates and the customer's destination coordinates to call the Armi API (`order/delivery-cost`) and fetch the shipping price.
    *   The calculated shipping cost is displayed to the customer.
4.  **Order Placement & Vendor Management**:
    *   Once the customer completes the order, it appears in the vendor's CS-Cart order list.
    *   On the order details page, the vendor sees Armi-specific options:
        *   An option to "Create Armi Order." Clicking this sends the necessary order details (including customer lat/long, product info, and vendor-selected vehicle type) to the Armi `monitor/order/create` API. The Armi `payment_method` defaults to '3' (Online Transaction) if the CS-Cart order is prepaid, but vendors can override this.
        *   The Armi order ID (once created) and the current Armi order status (fetched from Armi API) are displayed.
        *   An option to "Cancel Armi Order," which calls the Armi `monitor/order/cancel` API.

## 4. Key Success Metrics
*   High adoption rate by vendors (if applicable to the store's model).
*   Accurate shipping cost calculations matching Armi's rates.
*   Reduction in shipping-related customer service inquiries.
*   Positive feedback from customers and vendors regarding ease of use.
*   Successful and reliable synchronization of order data between CS-Cart and Armi.
