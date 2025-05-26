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
