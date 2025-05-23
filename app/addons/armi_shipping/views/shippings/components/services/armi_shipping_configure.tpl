{** Armi Shipping Configuration Template for Instance-Specific Settings (Vendor-Managed) **}

{* This template is loaded into the "Configure" tab when a vendor edits their Armi shipping method. *}
{* Settings saved here are stored in $shipping.service_params. *}

<div class="control-group">
    <label class="control-label" for="armi_api_key">{__("armi_shipping.api_key")}:</label>
    <div class="controls">
        <input type="text" 
               name="shipping_data[service_params][armi_api_key]" 
               id="armi_api_key" 
               value="{$shipping.service_params.armi_api_key|default:""}" 
               class="input-text input-large" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="armi_business_id">{__("armi_shipping.business_id")}:</label>
    <div class="controls">
        <input type="text" 
               name="shipping_data[service_params][armi_business_id]" 
               id="armi_business_id" 
               value="{$shipping.service_params.armi_business_id|default:""}" 
               class="input-text input-large" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="armi_branch_office_id">{__("armi_shipping.branch_office_id")}:</label>
    <div class="controls">
        <input type="text" 
               name="shipping_data[service_params][armi_branch_office_id]" 
               id="armi_branch_office_id" 
               value="{$shipping.service_params.armi_branch_office_id|default:""}" 
               class="input-text input-large" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="armi_country_code">{__("armi_shipping.country_code")}:</label>
    <div class="controls">
        <select name="shipping_data[service_params][armi_country_code]" id="armi_country_code">
            <option value="VEN" {if $shipping.service_params.armi_country_code == "VEN"}selected="selected"{/if}>{__("armi_shipping.country_ven")}</option>
            <option value="COL" {if $shipping.service_params.armi_country_code == "COL"}selected="selected"{/if}>{__("armi_shipping.country_col")}</option>
            {* Add other countries as needed *}
        </select>
    </div>
</div>

<hr />
<h4>{__("armi_shipping.origin_location")}</h4>

<div class="control-group">
    <label class="control-label" for="armi_origin_latitude">{__("latitude")}:</label>
    <div class="controls">
        <input type="text" 
               name="shipping_data[service_params][origin_latitude]" 
               id="armi_origin_latitude" 
               value="{$shipping.service_params.origin_latitude|default:""}" 
               class="input-text armi-latitude" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="armi_origin_longitude">{__("longitude")}:</label>
    <div class="controls">
        <input type="text" 
               name="shipping_data[service_params][origin_longitude]" 
               id="armi_origin_longitude" 
               value="{$shipping.service_params.origin_longitude|default:""}" 
               class="input-text armi-longitude" />
    </div>
</div>

<div class="control-group">
    <div class="controls">
        <div id="armi_origin_map_canvas" style="width: 100%; height: 400px; border: 1px solid #ccc;">
            {__("armi_shipping.map_loading")}
        </div>
        <p class="muted description">{__("armi_shipping.map_interaction_hint_configure")}</p>
    </div>
</div>

{* 
    Hidden input for Google Maps API Key - this should ideally come from global addon settings.
    For now, assuming it's available in the template context or will be added via JS.
    If Tygh::$app.addons.armi_shipping.google_maps_api_key is available:
    <input type="hidden" id="armi_google_maps_api_key_configure" value="{Tygh::$app.addons.armi_shipping.google_maps_api_key}" />
*}

{* 
    JavaScript for this map will need to be loaded.
    Example: <script type="text/javascript" src="{$config.current_location}/js/addons/armi_shipping/configure_map.js"></script> 
    And that JS file would initialize the map for #armi_origin_map_canvas, #armi_origin_latitude, #armi_origin_longitude
*}
