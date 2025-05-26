{** This hook is executed on the order details page in the admin/vendor panel **}
{** We will add Armi order management options here **}

{assign var="is_armi_shipping_order" value=false}
{assign var="armi_shipping_id_used" value=null}
{if $order_info.shipping}
    {foreach from=$order_info.shipping item=shipping_method_instance}
        {if $shipping_method_instance.module == 'armi_shipping'}
            {$is_armi_shipping_order = true}
            {$armi_shipping_id_used = $shipping_method_instance.shipping_id}
            {break}
        {/if}
    {/foreach}
{/if}

{if $is_armi_shipping_order}
    {assign var="armi_order_data" value=fn_armi_shipping_get_order_data($order_info.order_id)}

    <div class="sidebar-row">
        <h6>{__("armi_shipping_management")}</h6> {* Add lang var *}
        <div class="control-group">
            {if $armi_order_data.armi_order_id}
                <p>
                    <strong>{__("armi_order_id_label")}:</strong> {$armi_order_data.armi_order_id} {* Add lang var *}
                </p>
                {if $armi_order_data.armi_last_status_code !== null}
                    <p>
                        <strong>{__("armi_current_status_label")}:</strong> {__("armi_status_`$armi_order_data.armi_last_status_code`")|default:$armi_order_data.armi_last_status_code} {* Add lang var pattern *}
                    </p>
                {/if}
                {* Add "Cancel Armi Order" button here later *}
                {* Add "Refresh Status" button here later *}
            {else}
                <form action="{""|fn_url}" method="post" name="armi_create_order_form">
                    <input type="hidden" name="dispatch" value="armi_management.create_armi_order" />
                    <input type="hidden" name="order_id" value="{$order_info.order_id}" />
                    <input type="hidden" name="redirect_url" value="{$config.current_url}" />

                    <div class="control-group">
                        <label class="control-label cm-required" for="armi_vehicle_type_{$order_info.order_id}">{__("armi_vehicle_type")}:</label>
                        <div class="controls">
                            <select name="armi_vehicle_type" id="armi_vehicle_type_{$order_info.order_id}">
                                <option value="1">{__("armi_vehicle_type_1")}</option> {* Bicicleta - Add lang var *}
                                <option value="2" selected="selected">{__("armi_vehicle_type_2")}</option> {* Motocicleta - Add lang var *}
                                <option value="3">{__("armi_vehicle_type_3")}</option> {* Carro - Add lang var *}
                            </select>
                        </div>
                    </div>
                    
                    {* Optionally, allow overriding payment method if needed, for now controller defaults it *}
                    
                    <div class="buttons-container">
                        {include file="buttons/button.tpl" but_text=__("armi_create_armi_order") but_role="submit" but_name="dispatch[armi_management.create_armi_order]"}
                    </div>
                </form>
            {/if}
        </div>
    </div>
{/if}
