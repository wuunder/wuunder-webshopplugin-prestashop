<div class="delivery_option alternate_item parcelshop_container">

</div>
<script type="text/javascript">
{literal}
initParcelshopLocator();
var shippingCarrierId = "{/literal}{$carrier_id}{literal}";
// Get the modal
var parcelshopShippingMethodElem = jQuery('[value="' + shippingCarrierId + ',"].delivery_option_radio')[0];
console.log(parcelshopShippingMethodElem);
var shippingAddress;
var parcelshopAddress;

var baseUrl;
var baseUrlApi;
var availableCarrierList;

function initParcelshopLocator(url, apiUrl, carrierList) {
    baseUrl = url;
    baseUrlApi = apiUrl;
    availableCarrierList = carrierList;

    if (parcelshopShippingMethodElem) {
        parcelshopShippingMethodElem.onchange = _onShippingMethodChange;
        _onShippingMethodChange();
    }
}

function _onShippingMethodChange() {
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

    jQuery.post( baseUrl + "admin-ajax.php", {action: 'wuunder_parcelshoplocator_get_address', address: address}, function( data ) {
        console.log(data);
        shippingAddress = data;
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
        window.parent.document.getElementById('parcelshop_id').value = messageData.parcelshopId;
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
    jQuery.post( baseUrl + "admin-ajax.php", {action: 'wuunder_parcelshoplocator_get_parcelshop_address', parcelshop_id: id}, function( data ) {
        data = JSON.parse(data);
        var parcelshopInfoHtml = _capFirst(data.company_name) + "<br>" + _capFirst(data.address.street_name) +
            " " + data.address.house_number + "<br>" + data.address.city;
        parcelshopInfoHtml = parcelshopInfoHtml.replace(/"/g, '\\"').replace(/'/g, "\\'");
        console.log(parcelshopInfoHtml);
        parcelshopAddress = parcelshopInfoHtml;
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

