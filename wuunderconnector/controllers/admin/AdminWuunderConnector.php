<?php

if (!defined('_PS_VERSION_'))
    exit;

//use AdminTab;

class AdminWuunderConnectorController extends ModuleAdminController
{

    public function __construct()
    {
        parent::__construct();
        $this->name = 'wuunder';
        $this->logger = new FileLogger(0); //0 == debug level, logDebug() won’t work without this.
        $this->logger->setFilename(_PS_ROOT_DIR_ . ((_PS_VERSION_ < '1.7') ? "/log/wuunder.log" : "/app/logs/wuunder.log"));
        $this->bootstrap = true;
        $this->override_folder = "";
        $this->sourceObj = array("product" => "Prestashop extension", "version" => array("build" => "1.2.5", "plugin" => "1.0"));
    }

    private function setBookingToken($order_id, $booking_url, $booking_token)
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'wuunder_shipments (order_id, booking_url, booking_token)
                    VALUES (' . $order_id . ', "' . $booking_url . '", "' . $booking_token . '")';
        if (Db::getInstance()->Execute($sql)) {
            return true;
        } else {
            $this->logger->logDebug(Db::getInstance()->getMsgError());
        }
    }

    private function getBookingUrlForOrder($order_id)
    {
        $sql = 'SELECT booking_url FROM ' . _DB_PREFIX_ . 'wuunder_shipments WHERE order_id = ' . $order_id;
        $result = Db::getInstance()->ExecuteS($sql);
        if ($result) {
            return $result[0]['booking_url'];
        }
        return false;
    }

    private function getOrdersInfo()
    {
//        $fieldlist = array('O.`id_order`', 'O.`id_cart`', 'AD.`lastname`', 'AD.`firstname`', 'AD.`postcode`', 'AD.`city`', 'CL.`iso_code`', 'C.`email`', 'CA.`name`');
        $fieldlist = array('O.*', 'AD.*', 'CL.iso_code', 'WS.label_url', 'WS.booking_url', 'WS.label_tt_url');

        $sql = 'SELECT  ' . implode(', ', $fieldlist) . '
                    FROM    ' . _DB_PREFIX_ . 'orders AS O LEFT JOIN ' . _DB_PREFIX_ . 'wuunder_shipments AS WS ON O.id_order = WS.order_id, 
                            ' . _DB_PREFIX_ . 'carrier AS CA, 
                            ' . _DB_PREFIX_ . 'customer AS C, 
                            ' . _DB_PREFIX_ . 'address AS AD, 
                            ' . _DB_PREFIX_ . 'country AS CL
                    WHERE   O.id_address_delivery=AD.id_address AND
                            C.id_customer=O.id_customer AND 
                            CL.id_country=AD.id_country AND 
                            CA.id_carrier=O.id_carrier
                    GROUP BY O.id_order
                    ORDER BY id_order DESC';
        return Db::getInstance()->ExecuteS($sql);
    }

    private function getOrderInfo($order_id)
    {
        $fieldlist = array('O.*', 'AD.*', 'CL.iso_code', 'C.email', 'SUM(OD.product_weight) as weight', 'MIN(OD.product_id) as id_product', 'GROUP_CONCAT(OD.product_name SEPARATOR ". ") as description');

        $sql = 'SELECT  ' . implode(', ', $fieldlist) . '
                    FROM    ' . _DB_PREFIX_ . 'orders AS O, 
                            ' . _DB_PREFIX_ . 'carrier AS CA, 
                            ' . _DB_PREFIX_ . 'customer AS C, 
                            ' . _DB_PREFIX_ . 'address AS AD, 
                            ' . _DB_PREFIX_ . 'country AS CL,
                            ' . _DB_PREFIX_ . 'order_detail AS OD
                    WHERE   O.id_address_delivery=AD.id_address AND
                            C.id_customer=O.id_customer AND 
                            CL.id_country=AD.id_country AND 
                            CA.id_carrier=O.id_carrier AND
                            O.id_order=OD.id_order AND
                            O.id_order=' . $order_id . '
                    LIMIT 1';
        return Db::getInstance()->ExecuteS($sql)[0];
    }

    private function getOrderProductDetails($product_id)
    {
        $fieldlist = array('width, height, depth');

        $sql = 'SELECT  ' . implode(', ', $fieldlist) . '
                    FROM    ' . _DB_PREFIX_ . 'product
                    WHERE   id_product=' . $product_id;
        return Db::getInstance()->ExecuteS($sql)[0];
    }

    public function getOrderState($params, $_)
    {
        $order_state_id = $params['state_id'];
        if (!$order_state_id)
            return false;
        // else, returns an OrderState object
        return (new OrderState($order_state_id, Configuration::get('PS_LANG_DEFAULT')))->name;
    }

    private function addressSplitter($address, $address2 = null, $address3 = null)
    {

        if (!isset($address)) {
            return false;
        }

        if (isset($address2) && $address2 != '' && isset($address3) && $address3 != '') {

            $result['streetName'] = $address;
            $result['houseNumber'] = $address2;
            $result['houseNumberSuffix'] = $address3;

        } else if (isset($address2) && $address2 != '') {

            $result['streetName'] = $address;

            // Pregmatch pattern, dutch addresses
            $pattern = '#^([0-9]{1,5})([a-z0-9 \-/]{0,})$#i';

            preg_match($pattern, $address2, $houseNumbers);

            $result['houseNumber'] = $houseNumbers[1];
            $result['houseNumberSuffix'] = (isset($houseNumbers[2])) ? $houseNumbers[2] : '';

        } else {

            // Pregmatch pattern, dutch addresses
            $pattern = '#^([a-z0-9 [:punct:]\']*) ([0-9]{1,5})([a-z0-9 \-/]{0,})$#i';

            preg_match($pattern, $address, $addressParts);

            $result['streetName'] = isset($addressParts[1]) ? $addressParts[1] : $address;
            $result['houseNumber'] = isset($addressParts[2]) ? $addressParts[2] : "";
            $result['houseNumberSuffix'] = (isset($addressParts[3])) ? $addressParts[3] : '';
        }

        //$this->log('After split => 1) '.$result['streetName'].' / 2) '.$result['houseNumber'].' / 3) '.$result['houseNumberSuffix']);
        return $result;
    }

    private function buildWuunderData($order_info)
    {
//        echo "<pre>";
//        var_dump($order_info);
//        echo "</pre>";
//        exit;
        $shippingAddress = new Address(intval($order_info['id_address_delivery']));

        // Get full address, strip enters/newlines etc
        $addressLine = trim(preg_replace('/\s+/', ' ', $shippingAddress->address1));

        // Splitt addres in 3 parts
        $addressParts = $this->addressSplitter($addressLine);
        $streetName = $addressParts['streetName'];
        $houseNumber = $addressParts['houseNumber'] . $addressParts['houseNumberSuffix'];

        $customerAdr = array(
            'business' => $shippingAddress->company,
            'email_address' => $order_info['email'],
            'family_name' => $shippingAddress->lastname,
            'given_name' => $shippingAddress->firstname,
            'locality' => $shippingAddress->city,
            'phone_number' => $shippingAddress->phone,
            'street_name' => $streetName,
            'house_number' => $houseNumber,
            'zip_code' => $shippingAddress->postcode,
            'country' => $order_info['iso_code']
        );

        $webshopAdr = array(
            'business' => Configuration::get('company_name'),
            'email_address' => Configuration::get('email'),
            'family_name' => Configuration::get('lastname'),
            'given_name' => Configuration::get('firstname'),
            'locality' => Configuration::get('city'),
            'phone_number' => Configuration::get('phonenumber'),
            'street_name' => Configuration::get('streetname'),
            'house_number' => Configuration::get('housenumber'),
            'zip_code' => Configuration::get('zipcode'),
            'country' => Configuration::get('country')
        );

        $orderAmountExclVat = intval($order_info['total_products'] * 100);

        // Load product image for first ordered item
        $image = null;
        if (file_exists('../img/p/' . $order_info['id_product'] . '/' . $order_info['id_product'] . '-home_default.jpg')) {
            $image = base64_encode(file_get_contents('../img/p/' . $order_info['id_product'] . '/' . $order_info['id_product'] . '-home_default.jpg'));
        }

        $product_data = $this->getOrderProductDetails($order_info['id_product']);
        $length = round($product_data['depth']);
        $width = round($product_data['width']);
        $height = round($product_data['height']);

        switch (Configuration::get('PS_DIMENSION_UNI')) {
            case "mm":
                $dimension_product_factor = 10;
                break;
            case "cm":
                $dimension_product_factor = 1;
                break;
            case "dm":
                $dimension_product_factor = 0.1;
                break;
            case "m":
                $dimension_product_factor = 0.01;
                break;
            default:
                $dimension_product_factor = 1;
                break;
        }

        $product_length = ($length > 0) ? round($length * $dimension_product_factor) : null;
        $product_width = ($width > 0) ? round($width * $dimension_product_factor) : null;
        $product_height = ($height > 0) ? round($height *$dimension_product_factor) : null;

        $preferredServiceLevel = "";

        for ($i = 1; $i < 5; $i++) {
            if (Configuration::get('wuunderfilter' . $i . 'carrier') == $order_info['id_carrier']) {
                $preferredServiceLevel = Configuration::get('wuunderfilter' . $i . 'filter');
                break;
            }
        }

        return array(
            'description' => $order_info['description'],
            'personal_message' => "",
            'picture' => $image,
            'customer_reference' => $order_info['id_order'],
            'packing_type' => "",
            'value' => $orderAmountExclVat,
            'kind' => null,
            'length' => $product_length,
            'width' => $product_width,
            'height' => $product_height,
            'weight' => intval($order_info['weight']),
            'delivery_address' => $customerAdr,
            'pickup_address' => $webshopAdr,
            'preferred_service_level' => $preferredServiceLevel,
            'source' => $this->sourceObj
        );
    }

    private function requestBookingUrl($order_id)
    {
        $booking_url = $this->getBookingUrlForOrder($order_id);
        if (!$booking_url || empty($booking_url)) {
            // Fetch order
            $order = $this->getOrderInfo($order_id);
//        echo Db::getInstance()->getMsgError();

            // Get configuration
            $test_mode = Configuration::get('testmode');
            $booking_token = uniqid();
            $link = new Link();
            $path = explode('/', _PS_ADMIN_DIR_);
            $redirect_url = urlencode(((_PS_VERSION_ < '1.7') ? _PS_BASE_URL_ . __PS_BASE_URI__ . end($path) . "/" : "") . $link->getAdminLink('AdminWuunderConnector', true));
            $webhook_url = urlencode(_PS_BASE_URL_ . __PS_BASE_URI__ . "index.php?fc=module&module=wuunderconnector&controller=wuunderwebhook&orderid=" . $order_id . "&wtoken=" . $booking_token);

            if ($test_mode == 1) {
                $apiUrl = 'https://api-staging.wuunder.co/api/bookings?redirect_url=' . $redirect_url . '&webhook_url=' . $webhook_url;
                $apiKey = Configuration::get('test_api_key');
            } else {
                $apiUrl = 'https://api.wuunder.co/api/bookings?redirect_url=' . $redirect_url . '&webhook_url=' . $webhook_url;
                $apiKey = Configuration::get('live_api_key');
            }

            // Combine wuunder info and order data
            $wuunderData = $this->buildWuunderData($order);
            // Encode variables
            $json = json_encode($wuunderData);
            // Setup API connection
            $cc = curl_init($apiUrl);

            curl_setopt($cc, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $apiKey, 'Content-type: application/json'));
            curl_setopt($cc, CURLOPT_POST, 1);
            curl_setopt($cc, CURLOPT_POSTFIELDS, $json);
            curl_setopt($cc, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cc, CURLOPT_VERBOSE, 1);
            curl_setopt($cc, CURLOPT_HEADER, 1);

            // Don't log base64 image string
            $wuunderData['picture'] = 'base64 string removed for logging';

            // Execute the cURL, fetch the XML
            $result = curl_exec($cc);
            $header_size = curl_getinfo($cc, CURLINFO_HEADER_SIZE);
            $header = substr($result, 0, $header_size);
            preg_match("!\r\n(?:Location|URI): *(.*?) *\r\n!i", $header, $matches);
            $url = $matches[1];

            // Close connection
            curl_close($cc);

            $infoArray['booking_url'] = $url;
            $this->setBookingToken($order_id, $url, $booking_token);
            Tools::redirect($url);
        } else {
            Tools::redirect($booking_url);
        }
    }

    public function setTemplate($template, $params = array(), $locale = null)
    {
        if (strpos($template, 'module:') === 0) {
            $this->template = $template;
        } else {
            parent::setTemplate($template, $params, $locale);
        }
    }

    public function initContent()
    {
        $order_info = $this->getOrdersInfo();

        if (isset($_REQUEST['processLabelForOrder'])) {
            $this->requestBookingUrl($_REQUEST['processLabelForOrder']);
        }

        $link = new Link();
        $path = explode('/', _PS_ADMIN_DIR_);
        Context::getContext()->smarty->registerPlugin("function", "order_state", array($this, 'getOrderState'));
        Context::getContext()->smarty->assign(array(
            'order_info' => $order_info,
            'admin_url' => ((_PS_VERSION_ < '1.7') ? _PS_BASE_URL_ . __PS_BASE_URI__ . end($path) . "/" : "") . $link->getAdminLink('AdminWuunderConnector', true),
        ));
        $this->setTemplate('AdminWuunderConnector.tpl');
        parent::initContent();
    }
}