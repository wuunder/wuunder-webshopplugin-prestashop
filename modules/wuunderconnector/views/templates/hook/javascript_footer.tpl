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
 *  @copyright 2015-2019 Wuunder Holding B.V.
 *  @license   LICENSE.txt
 *}

 {block name='javascript_bottom'}
<script type="text/javascript">
{literal}
var shippingCarrierId = "{/literal}{$carrier_id|escape:'htmlall':'UTF-8'}{literal}";
// Get the modal
var parcelshopAddress = {/literal}{$cookieParcelshopAddress|@json_encode|escape:'quotes':'UTF-8'}{literal};
if (parcelshopAddress !== "") {
    var parcelshopId = "{/literal}{$cookieParcelshopId|escape:'htmlall':'UTF-8'}{literal}";
}

var addressId = {/literal}{$addressId|escape:'htmlall':'UTF-8'}{literal};
var baseUrl = '{/literal}{$baseUrl|escape:'htmlall':'UTF-8'}{literal}';
var baseApiUrl = '{/literal}{$baseApiUrl|escape:'htmlall':'UTF-8'}{literal}';
var availableCarriers ='{/literal}{$availableCarriers|escape:'htmlall':'UTF-8'}{literal}';
var selectParcelshopLink = '<div id="parcelshopsSelectedContainer"><a href="#/" onclick="_showParcelshopLocator()" id="selectParcelshop">{/literal}{l s='Click here to select your parcelshop' mod='wuunderconnector'}{literal}</a></div>';
var parcelshopHtmlPrefix = '<br/><strong>{/literal}{l s='Current parcelshop:' mod='wuunderconnector'}{literal}</strong><br/>';
var parcelshopSelectDifferent = '{/literal}{l s='Click here to select a different parcelshop' mod='wuunderconnector'}{literal}'; 
{/literal}</script>
<script src={$jsFile|escape:'htmlall':'UTF-8'}></script>
{/block}