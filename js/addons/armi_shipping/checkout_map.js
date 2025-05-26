(function(_, $) {
    let armiMaps = {}; // To store map instances and markers
    let googleApiLoaded = false;
    let googleApiLoading = false;

    const defaultCenter = { lat: 6.4238, lng: -66.5897 }; // Venezuela
    const defaultZoom = 6;
    const markerZoom = 15;

    // Standard CS-Cart address field selectors (adjust if necessary for your theme/version)
    const addressFieldSelectors = [
        '#litecheckout_s_address',
        '#litecheckout_s_address_2',
        '#litecheckout_s_city',
        '#litecheckout_s_state',
        '#litecheckout_s_zipcode',
        // May also need to target labels or parent containers
        'label[for="litecheckout_s_address"]',
        'label[for="litecheckout_s_city"]',
        'label[for="litecheckout_s_state"]',
        'label[for="litecheckout_s_zipcode"]'
    ];

    const methods = {
        loadGoogleMapsApi: function() {
            if (googleApiLoaded || googleApiLoading) {
                if(googleApiLoaded) methods.initializeAllMaps(); // If already loaded, ensure maps are init
                return;
            }

            const apiKey = Tygh.armi_shipping_checkout_params && Tygh.armi_shipping_checkout_params.google_maps_api_key;
            if (!apiKey) {
                console.error('Armi Shipping: Google Maps API Key not found.');
                return;
            }

            googleApiLoading = true;
            const script = document.createElement('script');
            script.id = 'armiCheckoutMapScript';
            script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=armiCheckoutMapGlobalCallback`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);

            window.armiCheckoutMapGlobalCallback = function() {
                console.log('Armi Shipping: Google Maps API loaded.');
                googleApiLoaded = true;
                googleApiLoading = false;
                methods.initializeAllMaps();
            };
        },

        initializeAllMaps: function() {
            if (!googleApiLoaded || !Tygh.armi_shipping_checkout_params || !Tygh.armi_shipping_checkout_params.instances) {
                return;
            }
            Tygh.armi_shipping_checkout_params.instances.forEach(instance => {
                const radioInput = $(`input[name="shipping_ids[${instance.group_key}]"][value="${instance.shipping_id}"]`);
                if (radioInput.is(':checked')) {
                    methods.initializeInstanceMap(instance);
                }
            });
        },

        initializeInstanceMap: function(instance) {
            if (!googleApiLoaded) {
                console.warn("Armi Shipping: Google Maps API not loaded yet, deferring map initialization for " + instance.shipping_id);
                methods.loadGoogleMapsApi(); // Attempt to load if not already
                return;
            }
            if (armiMaps[instance.shipping_id] && armiMaps[instance.shipping_id].map) {
                // Map already initialized for this instance
                return;
            }

            const mapContainerElement = document.getElementById(instance.map_canvas_id);
            if (!mapContainerElement) {
                console.error('Armi Shipping: Map container not found for ID:', instance.map_canvas_id);
                return;
            }
            $(mapContainerElement).empty().show(); // Clear "Loading..." message

            const initialLat = parseFloat($(`#${instance.lat_input_id}`).val()) || defaultCenter.lat;
            const initialLng = parseFloat($(`#${instance.lng_input_id}`).val()) || defaultCenter.lng;
            const initialZoom = ($(`#${instance.lat_input_id}`).val() && $(`#${instance.lng_input_id}`).val()) ? markerZoom : defaultZoom;

            const map = new google.maps.Map(mapContainerElement, {
                center: { lat: initialLat, lng: initialLng },
                zoom: initialZoom,
            });

            const marker = new google.maps.Marker({
                position: { lat: initialLat, lng: initialLng },
                map: map,
                draggable: true,
                title: _.tr('armi_select_location_on_map')
            });

            armiMaps[instance.shipping_id] = { map: map, marker: marker, instance: instance };

            google.maps.event.addListener(marker, 'dragend', function() {
                const newPosition = marker.getPosition();
                methods.updateCoordinates(instance, newPosition.lat(), newPosition.lng());
            });

            google.maps.event.addListener(map, 'click', function(event) {
                marker.setPosition(event.latLng);
                methods.updateCoordinates(instance, event.latLng.lat(), event.latLng.lng());
            });
            
            console.log('Armi Shipping: Map initialized for shipping ID ' + instance.shipping_id);
        },

        updateCoordinates: function(instance, lat, lng) {
            $(`#${instance.lat_input_id}`).val(lat.toFixed(8)).trigger('change');
            $(`#${instance.lng_input_id}`).val(lng.toFixed(8)).trigger('change');
            methods.saveCoordinates(lat, lng, instance);
            if(armiMaps[instance.shipping_id] && armiMaps[instance.shipping_id].map) {
                 armiMaps[instance.shipping_id].map.panTo({lat: lat, lng: lng});
            }
        },
        
        saveCoordinates: function(latitude, longitude, instance) {
            $.ceAjax('request', Tygh.fn_url('armi_shipping.save_coordinates'), {
                method: 'post',
                data: {
                    latitude: latitude,
                    longitude: longitude,
                    shipping_id: instance.shipping_id,
                    group_key: instance.group_key,
                    secure_hash: Tygh.security_hash // Important for CS-Cart AJAX
                },
                hidden: true, // To prevent default progress indicator
                callback: function(response) {
                    if (response.status === 'ok') {
                        console.log('Armi Shipping: Coordinates saved successfully for shipping ID ' + instance.shipping_id);
                    } else {
                        console.error('Armi Shipping: Failed to save coordinates for shipping ID ' + instance.shipping_id, response.message || '');
                        // Optionally, display a user-friendly error using $.ceNotification
                    }
                }
            });
        },

        handleShippingMethodChange: function() {
            let armiSelected = false;
            if (Tygh.armi_shipping_checkout_params && Tygh.armi_shipping_checkout_params.instances) {
                Tygh.armi_shipping_checkout_params.instances.forEach(instance => {
                    const radioInput = $(`input[name="shipping_ids[${instance.group_key}]"][value="${instance.shipping_id}"]`);
                    const mapContainer = $(`#${instance.container_id}`);

                    if (radioInput.is(':checked')) {
                        armiSelected = true;
                        mapContainer.show();
                        if (!googleApiLoaded) {
                            methods.loadGoogleMapsApi();
                        } else {
                             methods.initializeInstanceMap(instance);
                        }
                    } else {
                        mapContainer.hide();
                    }
                });
            }

            if (armiSelected) {
                methods.hideAddressFields();
            } else {
                methods.showAddressFields();
            }
        },

        hideAddressFields: function() {
            console.log('Armi Shipping: Hiding address fields.');
            addressFieldSelectors.forEach(selector => {
                $(selector).closest('.ty-control-group, .litecheckout__field').hide();
            });
        },

        showAddressFields: function() {
            console.log('Armi Shipping: Showing address fields.');
            addressFieldSelectors.forEach(selector => {
                 $(selector).closest('.ty-control-group, .litecheckout__field').show();
            });
        }
    };

    $(document).ready(function() {
        if (Tygh.armi_shipping_checkout_params && Tygh.armi_shipping_checkout_params.instances && Tygh.armi_shipping_checkout_params.instances.length > 0) {
            // Initial check and setup
            methods.handleShippingMethodChange();

            // Listen for changes on shipping method radio buttons
            // CS-Cart uses names like shipping_ids[0], shipping_ids[1] etc.
            $(Tygh.doc).on('change', 'input[name^="shipping_ids["]', function() {
                methods.handleShippingMethodChange();
            });
             // Also listen for AJAX updates that might re-render shipping methods
            $.ceEvent('on', 'ce.ajaxdone', function(elms) {
                // Check if shipping methods were part of the updated elements
                let shippingUpdated = false;
                elms.forEach(function(elm) {
                    if ($(elm).find('input[name^="shipping_ids["]').length > 0 || $(elm).is('input[name^="shipping_ids["]')) {
                        shippingUpdated = true;
                    }
                });
                if (shippingUpdated) {
                     console.log('Armi Shipping: Shipping methods updated via AJAX, re-evaluating.');
                     // Re-check params as they might have been updated by the TPL hook
                     if (Tygh.armi_shipping_checkout_params && Tygh.armi_shipping_checkout_params.instances && Tygh.armi_shipping_checkout_params.instances.length > 0) {
                        methods.handleShippingMethodChange();
                     }
                }
            });


        } else {
            console.log('Armi Shipping: No Armi instances found on this page or params not set.');
        }
        console.log('Armi Checkout Map JS initialized.');
    });

}(Tygh, Tygh.$));
