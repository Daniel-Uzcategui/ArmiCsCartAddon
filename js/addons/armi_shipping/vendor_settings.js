(function(_, $) {

    let map;
    let marker;
    let geocoder;

    function initArmiVendorMap() {
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            console.error('Google Maps API not loaded.');
            loadGoogleMapsApi(); // Attempt to load if not present
            return;
        }

        if (!Tygh.ArmiShippingVendor || !Tygh.ArmiShippingVendor.mapCanvasId) {
            // console.log('ArmiShippingVendor settings not available yet for company ID: ' + (Tygh.ArmiShippingVendor ? Tygh.ArmiShippingVendor.companyId : 'unknown'));
            return; // Data not ready
        }
        
        const mapCanvas = document.getElementById(Tygh.ArmiShippingVendor.mapCanvasId);
        if (!mapCanvas) {
            // console.error('Map canvas element not found: ' + Tygh.ArmiShippingVendor.mapCanvasId);
            return;
        }

        const latInput = document.getElementById(Tygh.ArmiShippingVendor.latInputId);
        const lngInput = document.getElementById(Tygh.ArmiShippingVendor.lngInputId);
        const locateMeButton = document.getElementById(Tygh.ArmiShippingVendor.locateMeButtonId);

        if (!latInput || !lngInput) {
            console.error('Latitude or Longitude input fields not found.');
            return;
        }

        const initialLat = parseFloat(Tygh.ArmiShippingVendor.originLat) || 10.4806; // Default to Caracas
        const initialLng = parseFloat(Tygh.ArmiShippingVendor.originLng) || -66.9036; // Default to Caracas

        map = new google.maps.Map(mapCanvas, {
            center: { lat: initialLat, lng: initialLng },
            zoom: (initialLat === 10.4806 && initialLng === -66.9036 && Tygh.ArmiShippingVendor.originLat == 0.0) ? 6 : 15, // Zoom out if default, else zoom in
            mapTypeControl: false,
            streetViewControl: false,
        });

        geocoder = new google.maps.Geocoder();

        marker = new google.maps.Marker({
            position: { lat: initialLat, lng: initialLng },
            map: map,
            draggable: true,
            title: Tygh.ArmiShippingVendor.lang.selectOriginOnMap || "Select origin on map"
        });

        // Update inputs when marker is placed or dragged
        function updateInputs(latLng) {
            latInput.value = latLng.lat().toFixed(8);
            lngInput.value = latLng.lng().toFixed(8);
        }

        updateInputs(marker.getPosition()); // Initial update

        marker.addListener('dragend', function() {
            updateInputs(marker.getPosition());
        });

        map.addListener('click', function(event) {
            marker.setPosition(event.latLng);
            updateInputs(event.latLng);
        });

        if (locateMeButton) {
            locateMeButton.addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        map.setCenter(userLocation);
                        marker.setPosition(userLocation);
                        updateInputs(userLocation);
                        map.setZoom(15);
                    }, function() {
                        alert('Error: The Geolocation service failed or permission was denied.');
                    });
                } else {
                    alert('Error: Your browser doesn\'t support geolocation.');
                }
            });
        }
    }

    function loadGoogleMapsApi() {
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
            // Already loaded
            initArmiVendorMap();
            return;
        }

        if (document.getElementById('google-maps-api-script-armi')) {
            // Script tag already added, waiting for it to load
            return;
        }

        const apiKey = Tygh.ArmiShippingVendor && Tygh.ArmiShippingVendor.googleMapsApiKey 
                       ? Tygh.ArmiShippingVendor.googleMapsApiKey 
                       : (Tygh.addons && Tygh.addons.armi_shipping ? Tygh.addons.armi_shipping.google_maps_api_key : null);

        if (!apiKey) {
            console.error('Google Maps API Key not found for Armi Shipping.');
            $('#' + (Tygh.ArmiShippingVendor ? Tygh.ArmiShippingVendor.mapCanvasId : 'armi_map_canvas_undefined')).html('<p style="color:red;">Google Maps API Key is missing. Please configure it in the addon settings.</p>');
            return;
        }

        const script = document.createElement('script');
        script.id = 'google-maps-api-script-armi';
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=onGoogleMapsApiLoadedArmiVendor&libraries=places,geometry&loading=async`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    // Define the callback function in the global scope
    window.onGoogleMapsApiLoadedArmiVendor = function() {
        initArmiVendorMap();
    };
    
    // Try to initialize when the tab is shown, as the elements might not exist on page load
    // if the tab is not active initially.
    $(document).ready(function() {
        // Check if the Armi settings tab exists and is potentially visible
        // The Tygh.ArmiShippingVendor object is populated by the inline script in the .tpl file
        // which is only rendered when the tab content is loaded.
        // So, if Tygh.ArmiShippingVendor.mapCanvasId is set, we are likely in the right context.
        
        // A more robust way for tabs loaded via AJAX or hidden initially:
        // Listen for an event or use a MutationObserver if the tab content is loaded dynamically.
        // For CS-Cart, tabs are often part of the initial page load or reloaded with specific AJAX requests.
        
        // If the settings are directly on the page (not in a separate AJAX-loaded tab)
        if (Tygh.ArmiShippingVendor && Tygh.ArmiShippingVendor.mapCanvasId) {
             loadGoogleMapsApi();
        } else {
            // If settings are in a tab that might be loaded later or activated
            // We can use a more generic approach or rely on CS-Cart's tab events if available.
            // For now, let's assume the inline script in the TPL will ensure data is ready.
            // A simple check on DOMContentLoaded or $(document).ready might be too early if the tab is hidden.
            // Let's try to initialize if the specific tab is clicked.
            $('a[href="#armi_settings"]').on('shown.bs.tab shown', function (e) {
                if (Tygh.ArmiShippingVendor && Tygh.ArmiShippingVendor.mapCanvasId) {
                    if (!map) { // Initialize only if not already done
                         loadGoogleMapsApi();
                    }
                }
            });
            // Also try if it's already active (e.g. on page refresh with tab active)
             if ($('#content_armi_settings').hasClass('active') || $('li.active > a[href="#armi_settings"]').length) {
                 if (Tygh.ArmiShippingVendor && Tygh.ArmiShippingVendor.mapCanvasId) {
                    if (!map) {
                         loadGoogleMapsApi();
                    }
                }
             }
        }
    });

}(Tygh, Tygh.$));
