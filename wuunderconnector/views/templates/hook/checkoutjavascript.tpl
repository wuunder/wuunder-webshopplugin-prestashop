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
* @author    DPD France S.A.S.
<support.ecommerce@dpd.fr>
* @copyright 2016 DPD France S.A.S.
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

<script type="text/javascript">
{literal}
var shippingCarrierId = "{/literal}{$carrier_id|escape:'htmlall':'UTF-8'}{literal}";
// Get the modal
var parcelshopShippingMethodElem = jQuery('[value="' + shippingCarrierId + ',"].delivery_option_radio')[0];
var shippingMethodElems = jQuery('input.delivery_option_radio');
var shippingAddress;
var parcelshopAddress = _markupParcelshopAddress({/literal}{$cookieParcelshopAddress|@json_encode|escape:'quotes':'UTF-8'}{literal});
var baseUrl;
var baseUrlApi;
var availableCarrierList;
var getAddressUrl = "index.php?fc=module&module=wuunderconnector&controller=parcelshop&getAddress=1";
var setParcelshopId = "index.php?fc=module&module=wuunderconnector&controller=parcelshop&setParcelshopId=1";
var addressId = {/literal}{$addressId|escape:'htmlall':'UTF-8'}{literal};
initParcelshopLocator('{/literal}{$baseUrl|escape:'htmlall':'UTF-8'}{literal}', '{/literal}{$baseApiUrl|escape:'htmlall':'UTF-8'}{literal}', '{/literal}{$availableCarriers|escape:'htmlall':'UTF-8'}{literal}');
function initParcelshopLocator(url, apiUrl, carrierList) {

    baseUrl = url;
    baseUrlApi = apiUrl;
    availableCarrierList = carrierList;
    
    jQuery('.delivery_options').append('<div class="delivery_option alternate_item parcelshop_container"></div>');

    if (parcelshopShippingMethodElem) {
        //parcelshopShippingMethodElem.onchange = _onShippingMethodChange;
        if (parcelshopAddress !== "") {
            parcelshopId = "{/literal}{$cookieParcelshopId|escape:'htmlall':'UTF-8'}{literal}";
        }
        //jQuery(shippingMethodElems).change(_onShippingMethodChange);
        jQuery(shippingMethodElems).on('change', _onShippingMethodChange);
        _onShippingMethodChange();
    }
}

function _onShippingMethodChange() {
    if (parcelshopShippingMethodElem.checked) {      
        var container = document.createElement('div');
        container.className += "chooseParcelshop";
        container.innerHTML = '<div id="parcelshopsSelectedContainer"><a href="#/" onclick="_showParcelshopLocator()" id="selectParcelshop">Klik hier om een parcelshop te kiezen</a></div>';
        // window.parent.document.getElementsByClassName('shipping')[0].appendChild(container);
        window.parent.document.getElementsByClassName('parcelshop_container')[0].appendChild(container);
        _printParcelshopAddress();
    } else {
        var containerElems = window.parent.document.getElementsByClassName('chooseParcelshop');
        if (containerElems.length) {
            containerElems[0].remove();
        }
    }
}

// add selected parcelshop to page
function _printParcelshopAddress() {
    if (parcelshopAddress) {
        if (window.parent.document.getElementsByClassName("parcelshopInfo").length) {
            window.parent.document.getElementsByClassName("parcelshopInfo")[0].remove();
        }
        var currentParcelshop = document.createElement('div');
        currentParcelshop.className += 'parcelshopInfo';
        currentParcelshop.innerHTML = '<br/><strong>Huidige Parcelshop:</strong><br/>' + parcelshopAddress;
        window.parent.document.getElementById('parcelshopsSelectedContainer').appendChild(currentParcelshop);
        window.parent.document.getElementById('selectParcelshop').innerHTML = 'klik hier om een andere parcelshop te kiezen';

    }
}


function _showParcelshopLocator() {
    var address = "";

    jQuery.post( baseUrl + getAddressUrl + "&addressId=" + addressId, function( data ) {
        shippingAddress = data["address1"] + ' ' + data["postcode"] + ' ' + data["city"] + ' ' + data["country"];
        _openIframe();
    });
}


function _openIframe() {
    var iframeUrl = baseUrlApi + 'parcelshop_locator/iframe/?lang=nl&availableCarriers=' + availableCarrierList + '&address=' + encodeURI(shippingAddress);

    var iframeContainer = document.createElement('div');
    iframeContainer.className = "parcelshopPickerIframeContainer";
    iframeContainer.onclick = function() { removeElement(iframeContainer); };
    var iframeDiv = document.createElement('div');
    iframeDiv.innerHTML = '<iframe src="' + iframeUrl + '" width="100%" height="100%">';
    iframeDiv.className = "parcelshopPickerIframe";
    iframeDiv.style.cssText = 'position: fixed; top: 0; left: 0; bottom: 0; right: 0; z-index: 2147483647';
    iframeContainer.appendChild(iframeDiv);
    window.parent.document.getElementsByClassName("chooseParcelshop")[0].appendChild(iframeContainer);

    function removeServicePointPicker() {
        removeElement(iframeContainer);
    }

    function onServicePointSelected(messageData) {
        removeServicePointPicker();
        _loadSelectedParcelshopAddress(messageData.parcelshopId);
    }

    function onServicePointClose() {
        removeServicePointPicker();
    }

    function onWindowMessage(event) {
        var origin = event.origin,
            messageData = event.data;
        var messageHandlers = {
            'servicePointPickerSelected': onServicePointSelected,
            'servicePointPickerClose': onServicePointClose
        };
        if (!(messageData.type in messageHandlers)) {
            alert('Invalid event type');
            return;
        }
        var messageFn = messageHandlers[messageData.type];
        messageFn(messageData);
    }

    window.addEventListener('message', onWindowMessage, false);
}

function _loadSelectedParcelshopAddress(id) {
        jQuery.post( baseUrl + setParcelshopId, {
                'parcelshopId' : id,
        }, function( data ) {
                parcelshopAddress = _markupParcelshopAddress(data);
                _printParcelshopAddress();
    });
}

function _markupParcelshopAddress(parcelshopData) {
                data = JSON.parse(parcelshopData);
                var parcelshopInfoHtml = _capFirst(data.company_name) + "<br>" + _capFirst(data.address.street_name) +
                " " + data.address.house_number + "<br>" + data.address.city;
                parcelshopInfoHtml = parcelshopInfoHtml.replace(/"/g, '\\"').replace(/'/g, "\\'");
                return parcelshopInfoHtml;
}

// Capitalizes first letter of every new word.
function _capFirst(str) {
    if (str === undefined)
        return "";
    return str.replace(/\w\S*/g, function (txt) {
        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
    });
}

function removeElement(element) {
    if (element.remove !== undefined) {
        element.remove();
    } else {
        element && element.parentNode && element.parentNode.removeChild(element);
    }
    
}

{/literal}</script>

