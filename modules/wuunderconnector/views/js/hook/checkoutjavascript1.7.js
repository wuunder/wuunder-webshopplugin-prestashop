/**
 * This file is part of the Prestashop Shipping module of Wuunder Nederland BV
 *
 * Copyright (C) 2017  Wuunder Nederland BV
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
$(window).on("load", function() {
    // Get the modal
    var parcelshopShippingMethodElem = jQuery('#delivery_option_' + shippingCarrierId);
    var shippingMethodElems = jQuery("input[name='" + $('#delivery_option_' + shippingCarrierId).attr('name') + "']");
    var shippingAddress;
    var getAddressUrl = "index.php?fc=module&module=wuunderconnector&controller=parcelshop&getAddress=1";
    var setParcelshopId = "index.php?fc=module&module=wuunderconnector&controller=parcelshop&setParcelshopId=1";
    var selectParcelshopLink = '<div id="parcelshopsSelectedContainer17"><a href="#/" id="selectParcelshop">' + innerHtml + '</a></div>';
    initParcelshopLocator(baseUrl, baseApiUrl, availableCarriers);

    function initParcelshopLocator(url, apiUrl, carrierList) {
    
        baseUrl = url;
        baseUrlApi = apiUrl;
        availableCarrierList = carrierList;
        parcelshopAddress = _markupParcelshopAddress(parcelshopAddress);
            
        if (parcelshopShippingMethodElem) {
            jQuery(shippingMethodElems).on('change', _onShippingMethodChange);
            _onShippingMethodChange();
        }
    }
    
    function _onShippingMethodChange() {
        if (parcelshopShippingMethodElem.prop('checked')) {      
            var container = document.createElement('div');
            container.className += "chooseParcelshop";
            container.innerHTML = selectParcelshopLink;
            jQuery('#delivery_option_' + shippingCarrierId).parentsUntil('#js-delivery > div > div.delivery-options').last().next().append(container);
            $("#selectParcelshop").on('click',_showParcelshopLocator);
            _printParcelshopAddress();
        } else {
            var containerElems = jQuery('#parcelshopsSelectedContainer17');
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
            currentParcelshop.innerHTML = parcelshopHtmlPrefix + parcelshopAddress;
            window.parent.document.getElementById('parcelshopsSelectedContainer17').appendChild(currentParcelshop);
            window.parent.document.getElementById('selectParcelshop').innerHTML = parcelshopSelectDifferent;
            $('#parcelshopsSelectedContainer17 > div').css({ 'font-size':$("#js-delivery > div > div.delivery-options > div:nth-child(1) > label > div > div.col-sm-4.col-xs-12 > span").css('font-size')})
        }
    }
    
    
    function _showParcelshopLocator() {
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
                    parcelshopHTML = parcelshopHtmlPrefix + parcelshopAddress;
                    _printParcelshopAddress();
        });
    }
    
    function _markupParcelshopAddress(parcelshopData) {
            if (!parcelshopData) {
                return false;
            }
            else {
                data = JSON.parse(parcelshopData);
                console.log(data);
                var parcelshopInfoHtml = _capFirst(data.company_name) + "<br>" + _capFirst(data.address.street_name) +
                " " + data.address.house_number + "<br>" + data.address.city;
                parcelshopInfoHtml = parcelshopInfoHtml.replace(/"/g, '\\"').replace(/'/g, "\\'");
                return parcelshopInfoHtml;
            }
    
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

});

