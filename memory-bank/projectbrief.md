# Project Brief: CS-Cart Armi Shipping Addon

## 1. Project Name
CS-Cart Armi Shipping Addon

## 2. Objective
Create a CS-Cart shipping addon to integrate with the Armi API for calculating delivery costs at checkout and managing Armi orders from the CS-Cart admin panel.

## 3. Target CS-Cart Version
CS-Cart Multi-Vendor Ultimate 4.18

## 4. Key Features
*   **Custom Shipping Method**: Implements a new shipping method specifically for Armi.
*   **Dynamic Delivery Cost Calculation**: Utilizes the Armi API (lat/long based) to calculate shipping costs at checkout.
*   **Google Maps Integration (Checkout)**:
    *   Customers use an embedded Google Map to select their precise delivery location.
    *   The selected latitude and longitude are used for cost calculation.
*   **Address Field Handling**: Hides standard CS-Cart address fields at checkout if Armi shipping is selected, as Armi relies on coordinates.
*   **Vendor-Specific Settings**:
    *   Armi API Key
    *   Armi Business ID
    *   Armi Branch Office ID (Origin)
    *   Armi API Country Code (e.g., COL, VEN)
    *   Origin Latitude (for delivery cost calculation)
    *   Origin Longitude (for delivery cost calculation)
*   **Google Maps Integration (Vendor Settings)**:
    *   Vendors use an embedded Google Map to set their origin latitude and longitude.
    *   Map to be centered in Venezuela by default.
    *   Include a "Locate Me" button if feasible with the Google Maps API.
*   **Order Management (Vendor Panel)**:
    *   After a CS-Cart order is placed, vendors can create the corresponding order in the Armi system from the CS-Cart order details page.
    *   Allow vendor to specify `vehicle_type` (Bicycle, Motorcycle, Car) when creating the Armi order.
    *   Vendors can cancel the Armi order from the CS-Cart order details page.
    *   Display Armi order status on the CS-Cart order details page.
*   **Addon Installation & Uninstallation**:
    *   Standard CS-Cart addon install/uninstall functions.
    *   Automatically register the custom shipping carrier ("Armi Logistics") and method ("Armi Delivery") upon installation.
*   **Global Addon Settings**:
    *   Google Maps API Key: `AIzaSyBALQyZf54Qvxz9xFkEUXnvQXrRp9P8GXI`

## 5. Author
*   **Name**: Unify
*   **Email**: info@unifyb2b.net
