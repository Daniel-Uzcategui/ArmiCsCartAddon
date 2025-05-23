{** Armi Shipping Vendor Settings Tab Content **}

{script src="js/addons/armi_shipping/vendor_settings.js"} {* We will create this JS file later *}

<div class="control-group">
    <label for="armi_api_key_{$company_id}" class="control-label">{__("armi_api_key")}:</label>
    <div class="controls">
        <input type="text" name="armi_settings[armi_api_key]" id="armi_api_key_{$company_id}" value="{$armi_settings.armi_api_key|default:''}" class="input-large" />
    </div>
</div>

<div class="control-group">
    <label for="armi_business_id_{$company_id}" class="control-label">{__("armi_business_id")}:</label>
    <div class="controls">
        <input type="text" name="armi_settings[armi_business_id]" id="armi_business_id_{$company_id}" value="{$armi_settings.armi_business_id|default:''}" class="input-medium" />
    </div>
</div>

<div class="control-group">
    <label for="armi_branch_office_id_{$company_id}" class="control-label">{__("armi_branch_office_id")}:</label>
    <div class="controls">
        <input type="text" name="armi_settings[armi_branch_office_id]" id="armi_branch_office_id_{$company_id}" value="{$armi_settings.armi_branch_office_id|default:''}" class="input-medium" />
    </div>
</div>

<div class="control-group">
    <label for="armi_country_code_{$company_id}" class="control-label">{__("armi_country_code")}:</label>
    <div class="controls">
        {* Consider making this a select dropdown if there's a fixed list, e.g., COL, VEN *}
        <input type="text" name="armi_settings[armi_country_code]" id="armi_country_code_{$company_id}" value="{$armi_settings.armi_country_code|default:''}" maxlength="3" class="input-small" placeholder="e.g. COL" />
    </div>
</div>

<hr />
<h4>{__("armi_origin_location")}</h4>

<div class="control-group">
    <label for="armi_origin_latitude_{$company_id}" class="control-label">{__("armi_latitude")}:</label>
    <div class="controls">
        <input type="text" name="armi_settings[origin_latitude]" id="armi_origin_latitude_{$company_id}" value="{$armi_settings.origin_latitude|default:'0.0'}" class="input-medium armi-latitude-input" readonly="readonly" />
    </div>
</div>

<div class="control-group">
    <label for="armi_origin_longitude_{$company_id}" class="control-label">{__("armi_longitude")}:</label>
    <div class="controls">
        <input type="text" name="armi_settings[origin_longitude]" id="armi_origin_longitude_{$company_id}" value="{$armi_settings.origin_longitude|default:'0.0'}" class="input-medium armi-longitude-input" readonly="readonly" />
    </div>
</div>

<div class="control-group">
    <div class="controls">
        <div id="armi_map_canvas_{$company_id}" style="width: 100%; height: 400px; margin-bottom: 10px;">
            {* Google Map will be initialized here by JavaScript *}
        </div>
        <button type="button" id="armi_locate_me_button_{$company_id}" class="btn">{__("armi_locate_me")}</button> {* We'll need this lang var *}
    </div>
</div>

{* Hidden input for Google Maps API key if needed by JS, or pass via Tygh.Registry *}
{* Tygh::$app.addons.armi_shipping.google_maps_api_key should be available from addon settings *}

<script type="text/javascript">
// Pass necessary data to JavaScript
Tygh.ArmiShippingVendor = Tygh.ArmiShippingVendor || {};
Tygh.ArmiShippingVendor.companyId = {$company_id};
Tygh.ArmiShippingVendor.googleMapsApiKey = '{$addons.armi_shipping.google_maps_api_key}';
Tygh.ArmiShippingVendor.originLat = {$armi_settings.origin_latitude|default:10.4806}; // Default to Caracas, Venezuela approx.
Tygh.ArmiShippingVendor.originLng = {$armi_settings.origin_longitude|default:-66.9036}; // Default to Caracas, Venezuela approx.
Tygh.ArmiShippingVendor.mapCanvasId = 'armi_map_canvas_{$company_id}';
Tygh.ArmiShippingVendor.latInputId = 'armi_origin_latitude_{$company_id}';
Tygh.ArmiShippingVendor.lngInputId = 'armi_origin_longitude_{$company_id}';
Tygh.ArmiShippingVendor.locateMeButtonId = 'armi_locate_me_button_{$company_id}';
Tygh.ArmiShippingVendor.lang = {
    selectOriginOnMap: '{__("armi_select_origin_on_map")}'
};
</script>
