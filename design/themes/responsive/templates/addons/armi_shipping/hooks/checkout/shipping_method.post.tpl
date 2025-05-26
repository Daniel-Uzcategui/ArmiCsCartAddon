{** This hook is executed for each shipping method displayed at checkout **}

{if $shipping.module == 'armi_shipping' && $shipping.service_code == 'armi_delivery'}
    {* This content will appear below the Armi Delivery shipping method option *}
    <div id="armi_shipping_map_container_{$group_key}_{$shipping.shipping_id}" class="ty-armi-shipping-map-container" style="margin-top: 10px; display: none;">
        <div class="ty-control-group">
            <label class="ty-control-group__label" for="armi_checkout_map_canvas_{$group_key}_{$shipping.shipping_id}">{__("armi_select_location_on_map")}:</label>
            <div id="armi_checkout_map_canvas_{$group_key}_{$shipping.shipping_id}" style="width: 100%; height: 300px; border: 1px solid #ccc;">
                {* Map will be initialized here by JavaScript *}
                <p style="padding: 10px;">{__("armi_shipping.map_loading")}</p>
            </div>
        </div>

        {* Hidden input fields to store the selected coordinates *}
        {* These names might need to be adjusted based on how checkout form data is structured and processed *}
        <input type="hidden" id="armi_destination_latitude_{$group_key}_{$shipping.shipping_id}" name="shippings_extra[armi_destination][{$group_key}][{$shipping.shipping_id}][latitude]" value="{$cart.shippings_extra.armi_destination[$group_key][$shipping.shipping_id].latitude|default:''}" />
        <input type="hidden" id="armi_destination_longitude_{$group_key}_{$shipping.shipping_id}" name="shippings_extra[armi_destination][{$group_key}][{$shipping.shipping_id}][longitude]" value="{$cart.shippings_extra.armi_destination[$group_key][$shipping.shipping_id].longitude|default:''}" />
        
        {* Pass shipping_id and group_key to JS if needed for unique map/field handling *}
        <input type="hidden" id="armi_shipping_id_data_{$group_key}_{$shipping.shipping_id}" value="{$shipping.shipping_id}" />
        <input type="hidden" id="armi_group_key_data_{$group_key}_{$shipping.shipping_id}" value="{$group_key}" />

        {* This script tag is a simple way to pass data to JS, could also use data-attributes on the container *}
        <script type="text/javascript">
            // Ensure Tygh.armi_shipping_checkout_params exists
            Tygh.armi_shipping_checkout_params = Tygh.armi_shipping_checkout_params || {};
            Tygh.armi_shipping_checkout_params.google_maps_api_key = Tygh.armi_shipping_checkout_params.google_maps_api_key || '{$armi_google_maps_api_key|escape:javascript nofilter}';
            
            // Store info about this specific Armi shipping method instance
            Tygh.armi_shipping_checkout_params.instances = Tygh.armi_shipping_checkout_params.instances || [];
            Tygh.armi_shipping_checkout_params.instances.push({
                shipping_id: {$shipping.shipping_id},
                group_key: '{$group_key}',
                map_canvas_id: 'armi_checkout_map_canvas_{$group_key}_{$shipping.shipping_id}',
                lat_input_id: 'armi_destination_latitude_{$group_key}_{$shipping.shipping_id}',
                lng_input_id: 'armi_destination_longitude_{$group_key}_{$shipping.shipping_id}',
                container_id: 'armi_shipping_map_container_{$group_key}_{$shipping.shipping_id}'
            });
        </script>
    </div>
{/if}
