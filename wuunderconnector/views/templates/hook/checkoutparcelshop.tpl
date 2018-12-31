<div class="delivery_option alternate_item parcelshop_container">
</div>
<script type="text/javascript">
{literal}
var shippingCarrierId = "{/literal}{$carrier_id}{literal}";
// Get the modal
var parcelshopShippingMethodElem = jQuery('[value="' + shippingCarrierId + ',"].delivery_option_radio')[0];
var shippingMethodElems = jQuery('input.delivery_option_radio');
console.log(parcelshopShippingMethodElem);
var shippingAddress;
var parcelshopAddress;

var baseUrl;
var baseUrlApi;
var availableCarrierList;
var getAddressUrl = "index.php?fc=module&module=wuunderconnector&controller=parcelshop&getAddress=1";
var addressId = {/literal}{$addressId}{literal};
initParcelshopLocator('{/literal}{$baseUrl}{literal}', '{/literal}{$baseApiUrl}{literal}', '{/literal}{$availableCarriers}{literal}');

function initParcelshopLocator(url, apiUrl, carrierList) {

    baseUrl = url;
    baseUrlApi = apiUrl;
    availableCarrierList = carrierList;

    if (parcelshopShippingMethodElem) {
        //parcelshopShippingMethodElem.onchange = _onShippingMethodChange;
        jQuery(shippingMethodElems).change(_onShippingMethodChange);
        _onShippingMethodChange();
    }
}

function _onShippingMethodChange() {
    console.log("here1", parcelshopShippingMethodElem.checked);
    if (parcelshopShippingMethodElem.checked) {      
        var container = document.createElement('div');
        container.className += "chooseParcelshop";
        container.innerHTML = '<div id="parcelshopsSelectedContainer" onclick="_showParcelshopLocator()"><a href="#/" id="selectParcelshop">Klik hier om een parcelshop te kiezen</a></div>';
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

function _printParcelshopAddress() {
    if (parcelshopAddress) {
        if (window.parent.document.getElementsByClassName("parcelshopInfo").length) {
            window.parent.document.getElementsByClassName("parcelshopInfo")[0].remove();
        }
        var currentParcelshop = document.createElement('div');
        currentParcelshop.className += 'parcelshopInfo';
        currentParcelshop.innerHTML = '<br/><strong>Huidige Parcelshop:</strong><br/>' + parcelshopAddress;
        window.parent.document.getElementById('parcelshopsSelectedContainer').appendChild(currentParcelshop);
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
        _loadSelectedParcelshopAddress(messageData.parcelshopId);
        removeServicePointPicker();
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
        jQuery.post( baseUrl + "module/wuunderconnector/parcelshop", {
                'method': 'setParcelShop',
                'parcelshopId' : this.id,
        }, function( data ) {
        console.log(data);
        _printParcelshopAddress();
    });
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
    
}{/literal}</script>

