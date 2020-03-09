{**
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
 *  @author    Wuunder Nederland BV
 *  @copyright 2015-2020 Wuunder Holding B.V.
 *  @license   LICENSE.txt
 *}

<link rel="stylesheet" type="text/css" href="../modules/wuunderconnector/views/css/admin/wuunder.css"/>
<link rel="stylesheet" type="text/css" href="../modules/wuunderconnector/views/css/admin/datatable.css"/>
<br>
<table id="wuunderTable" class="table order">
    <thead>
    <tr>
        <th>{l s='ID' mod='wuunderconnector'}</th>
        <th>{l s='Reference' mod='wuunderconnector'}</th>
        <th>{l s='Customer' mod='wuunderconnector'}</th>
        <th>{l s='Total' mod='wuunderconnector'}</th>
        <th>{l s='Payment' mod='wuunderconnector'}</th>
        <th>{l s='Status' mod='wuunderconnector'}</th>
        <th>{l s='Date' mod='wuunderconnector'}</th>
        <th>{l s='Actions' mod='wuunderconnector'}</th>
    </tr>
    </thead>
    <tbody>
    {if $version lt 1.7}
    {foreach from=$order_info item=order}
    <tr>
        <td>{$order.id_order|escape:'quotes':'UTF-8'}</td>
        <td>{$order.reference|escape:'quotes':'UTF-8'}</td>
        <td>{$order.firstname|escape:'quotes':'UTF-8'|substr:0:1}. {$order.lastname|escape:'quotes':'UTF-8'}</td>
        <td>{$order.total_paid|escape:'quotes':'UTF-8'|round:2}</td>
        <td>{$order.payment|escape:'quotes':'UTF-8'}</td>
        <td>{order_state state_id=$order.current_state|escape:'quotes':'UTF-8'}</td>
        <td>{$order.date_upd|escape:'quotes':'UTF-8'}</td>
        <td>
            <ul class="wuunder-action-list">
                {if !empty($order.label_url)}
                <li>
                    <a href="{$order.label_url|escape:'quotes':'UTF-8'}" target="_blank"><img
                                src="../modules/wuunderconnector/views/img/admin/print-label.png"/></a>
                </li>
                <li>
                    <a href="{$order.label_tt_url|escape:'quotes':'UTF-8'}" target="_blank"><img
                                src="../modules/wuunderconnector/views/img/admin/in-transit.png"/></a>
                </li>
                {elseif !empty($order.booking_url)}
                <li>
                    <a href="{$order.booking_url|escape:'quotes':'UTF-8'}"><img
                                src="../modules/wuunderconnector/views/img/admin/create-label.png"/></a>
                </li>
                {else}
                <li>
                    <a href="{$admin_url|escape:'quotes':'UTF-8'}&processLabelForOrder={$order.id_order|escape:'quotes':'UTF-8'}"><img
                                src="../modules/wuunderconnector/views/img/admin/create-label.png"/></a>
                </li>
                {/if}
            </ul>
        </td>
    </tr>
    {/foreach}
    {else}
    {foreach from=$order_info item=order}
    <tr>
        <td>{$order.id_order}</td>
        <td>{$order.reference}</td>
        <td>{$order.firstname}. {$order.lastname}</td>
        <td>{$order.total_paid|round:2}</td>
        <td>{$order.payment}</td>
        <td>{order_state state_id=$order.current_state}</td>
        <td>{$order.date_upd}</td>
        <td>
            <ul class="wuunder-action-list">
                {if !empty($order.label_url)}
                <li>
                    <a href="{$order.label_url}" target="_blank"><img
                                src="../modules/wuunderconnector/views/img/admin/print-label.png"/></a>
                </li>
                <li>
                    <a href="{$order.label_tt_url}" target="_blank"><img
                                src="../modules/wuunderconnector/views/img/admin/in-transit.png"/></a>
                </li>
                {elseif !empty($order.booking_url)}
                <li>
                    <a href="{$order.booking_url}"><img
                                src="../modules/wuunderconnector/views/img/admin/create-label.png"/></a>
                </li>
                {else}
                <li>
                    <a href="{$admin_url}&processLabelForOrder={$order.id_order}"><img
                                src="../modules/wuunderconnector/views/img/admin/create-label.png"/></a>
                </li>
                {/if}
            </ul>
        </td>
    </tr>
    {/foreach}
    {/if}
    </tbody>
</table>

<script src="../modules/wuunderconnector/views/js/admin/datatable.min.js"></script>
{literal}
<script>
    $(document).ready(function(){
        $('#wuunderTable').DataTable({
            responsive: true,
            order: [[ 0, 'desc' ]],
            autoWidth: true
        });
    });
</script>
{/literal}