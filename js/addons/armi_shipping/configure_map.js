(function(_, $) {
    let map, marker, geocoder;
    let defaultLat = 10.4806; // Caracas, Venezuela
    let defaultLng = -66.9036;
    let defaultZoom = 8;

    function initArmiConfigureMap() {
        const latInput = $('#armi_origin_latitude');
        const lngInput = $('#armi_origin_longitude');
        const mapCanvas = document.getElementById('armi_origin_map_canvas');

        if (!mapCanvas || !latInput.length || !lngInput.length) {
            console.error('Armi Shipping Configure: Map canvas or lat/lng inputs not found.');
            return;
        }

        // Use existing values if present, otherwise use defaults
        let currentLat = parseFloat(latInput.val()) || defaultLat;
        let currentLng = parseFloat(lngInput.val()) || defaultLng;
        let currentZoom = (latInput.val() && lngInput.val()) ? 15 : defaultZoom;

        map = new google.maps.Map(mapCanvas, {
            center: { lat: currentLat, lng: currentLng },
            zoom: currentZoom,
        });

        marker = new google.maps.Marker({
            position: { lat: currentLat, lng: currentLng },
            map: map,
            draggable: true,
            title: _.tr('armi_shipping.drag_to_set_origin') // Needs lang var
        });

        // Update inputs when marker is dragged
        marker.addListener('dragend', function(event) {
            latInput.val(event.latLng.lat().toFixed(8));
            lngInput.val(event.latLng.lng().toFixed(8));
            map.panTo(event.latLng);
        });

        // Update marker when map is clicked (alternative to dragging)
        map.addListener('click', function(event) {
            marker.setPosition(event.latLng);
            latInput.val(event.latLng.lat().toFixed(8));
            lngInput.val(event.latLng.lng().toFixed(8));
        });

        // Optional: Add a search box for addresses
        // const searchBoxContainer = $('<div style="padding: 10px;"></div>');
        // const searchInput = $('<input type="text" placeholder="' + _.tr('armi_shipping.search_location_placeholder') + '" style="width: 70%; padding: 5px;">'); // Needs lang var
        // const searchButton = $('<button type="button" class="btn" style="margin-left: 5px;">' + _.tr('search') + '</button>');
        // searchBoxContainer.append(searchInput).append(searchButton);
        // $(mapCanvas).before(searchBoxContainer);

        // geocoder = new google.maps.Geocoder();

        // searchButton.on('click', function() {
        //     const address = searchInput.val();
        //     if (address) {
        //         geocoder.geocode({ 'address': address }, function(results, status) {
        //             if (status === 'OK') {
        //                 map.setCenter(results[0].geometry.location);
        //                 marker.setPosition(results[0].geometry.location);
        //                 latInput.val(results[0].geometry.location.lat().toFixed(8));
        //                 lngInput.val(results[0].geometry.location.lng().toFixed(8));
        //                 map.setZoom(15);
        //             } else {
        //                 alert(_.tr('armi_shipping.geocode_failed') + ': ' + status); // Needs lang var
        //             }
        //         });
        //     }
        // });
    }

    // Load Google Maps API and initialize
    function loadGoogleMapsScriptConfigure() {
        // Check if Google Maps API is already loaded
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
            initArmiConfigureMap();
            return;
        }

        // IMPORTANT: The Google Maps API key should be retrieved securely,
        // ideally from a global addon setting passed to JS.
        // For now, this assumes `Tygh.addons.armi_shipping.google_maps_api_key` is set via a hook or script tag.
        // If not, this key needs to be hardcoded or fetched differently.
        // const apiKey = Tygh.addons.armi_shipping.google_maps_api_key; // Ideal
        const apiKeyEl = document.getElementById('armi_google_maps_api_key_configure'); // Fallback if hidden input is used
        let apiKey = '';
        if (apiKeyEl && apiKeyEl.value) {
            apiKey = apiKeyEl.value;
        } else if (Tygh.addons && Tygh.addons.armi_shipping && Tygh.addons.armi_shipping.google_maps_api_key) {
            apiKey = Tygh.addons.armi_shipping.google_maps_api_key;
        }


        if (!apiKey) {
            console.error('Armi Shipping Configure: Google Maps API Key not found.');
            $('#armi_origin_map_canvas').html('<p style="color: red;">' + _.tr('armi_shipping.error_google_maps_api_key_not_found') + '</p>'); // Needs lang var
            return;
        }

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=initArmiConfigureMapGlobal`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    // Make initArmiConfigureMap globally accessible for the callback
    window.initArmiConfigureMapGlobal = initArmiConfigureMap;

    // Initialize when the tab content is loaded (CS-Cart loads tab content via AJAX)
    // We need to ensure this runs when the "Configure" tab for Armi Shipping is displayed.
    // A robust way is to listen for an event or use a MutationObserver if CS-Cart doesn't provide a specific event.
    // For simplicity, we can try to initialize on document ready, but it might be too early for AJAX-loaded tabs.
    // A more specific trigger might be needed, e.g., if CS-Cart adds a class to the active tab content.

    $(document).ready(function() {
        // This might be too early if the configure tab is loaded via AJAX after page load.
        // Check if the specific map canvas exists before trying to load.
        if ($('#armi_origin_map_canvas').length) {
            loadGoogleMapsScriptConfigure();
        }

        // A more reliable way for AJAX-loaded tabs:
        // Listen for an event that signifies the tab content has been loaded.
        // Or, use a MutationObserver to detect when the map canvas appears in the DOM.
        // For example, if CS-Cart uses a specific class or ID for the loaded tab content:
        $.ceEvent('on', 'ce.tab.show', function(tab_id, jelm) {
            // Assuming the "Configure" tab for shippings has a predictable ID or content structure
            // This is a generic example; specific CS-Cart tab events might be different.
            if (jelm.find('#armi_origin_map_canvas').length) {
                 // Check if maps script is already loaded to prevent multiple loads
                if (typeof google === 'undefined' || typeof google.maps === 'undefined' || !map) {
                    loadGoogleMapsScriptConfigure();
                } else if (map && jelm.find('#armi_origin_map_canvas').html().includes(_.tr('armi_shipping.map_loading'))) {
                    // If map object exists but canvas was reloaded (e.g. tab re-clicked), re-init
                    initArmiConfigureMap();
                }
            }
        });
         // Fallback for initial load if the tab is already active
        if ($('.cm-tabs-content.active').find('#armi_origin_map_canvas').length) {
             if (typeof google === 'undefined' || typeof google.maps === 'undefined' || !map) {
                loadGoogleMapsScriptConfigure();
            }
        }
    });

})(Tygh, Tygh.$);
