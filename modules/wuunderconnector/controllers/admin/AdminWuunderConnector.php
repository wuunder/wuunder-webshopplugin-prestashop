<?php
/**

 * NOTICE OF LICENSE

 *

 * This file is licenced under the Software License Agreement.

 * With the purchase or the installation of the software in your application

 * you accept the licence agreement.

 *

 * You must not modify, adapt or create derivative works of this source code

 *

 *  @author    Wuunder Nederland BV

 *  @copyright 2015-2019 Wuunder Holding B.V.

 *  @license   LICENSE.txt

 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!defined('_PS_VERSION_')) {
    exit;
}

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
        $this->sourceObj = array("product" => "Prestashop extension", "version" => array("build" => "1.2.6", "plugin" => "1.0"));
    }

    private function setBookingToken($order_id, $booking_url, $booking_token)
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'wuunder_shipments (order_id, booking_url, booking_token)
                    VALUES (' . (int)$order_id . ', "' . pSQL($booking_url) . '", "' . pSQL($booking_token) . '")';
        if (Db::getInstance()->insert(
            'wuunder_shipments',
            array(
                'order_id' => (int)$order_id,
                'booking_url' => pSQL($booking_url),
                'booking_token' => pSQL($booking_token),
            )
        )
        ) {
            return true;
        } else {
            $this->logger->logDebug(Db::getInstance()->getMsgError());
        }
    }

    private function getBookingUrlForOrder($order_id)
    {
        $sql = 'SELECT booking_url FROM ' . _DB_PREFIX_ . 'wuunder_shipments WHERE order_id = ' . (int)$order_id;
        $result = Db::getInstance()->getValue($sql);
        if ($result) {
            return $result;
        }
        return false;
    }

    private function getParcelshopIdForOrder($order_id)
    {
        $sql = 'SELECT parcelshop_id FROM ' . _DB_PREFIX_ . 'wuunder_order_parcelshop WHERE order_id = ' . (int)$order_id;
        $result = Db::getInstance()->getValue($sql);
        if ($result) {
            return $result;
        }
        return false;
    }

    private function getOrdersInfo()
    {
        $fieldlist = array(
            'O.*', 'AD.*', 'CL.iso_code', 'WS.label_url', 'WS.booking_url',
            'WS.label_tt_url'
        );

        $sql = 'SELECT  ' . ((_PS_VERSION_ < "1.7") ? pSQL(implode(', ', $fieldlist)) : implode(', ', $fieldlist)) . '
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
        $sql = 'SELECT  O.*, AD.*, CL.iso_code, C.email, SUM(OD.product_weight) as weight, MIN(OD.product_id) as id_product, GROUP_CONCAT(OD.product_name SEPARATOR ". ") as description
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
                            O.id_order=' . (int)$order_id . '
                    LIMIT 1';
        return Db::getInstance()->ExecuteS($sql)[0];
    }

    private function getOrderProductDetails($product_id)
    {
        $fieldlist = array('width, height, depth');

        $sql = 'SELECT  ' . ((_PS_VERSION_ < "1.7") ? pSQL(implode(', ', $fieldlist)) : implode(', ', $fieldlist)) . '
                    FROM    ' . _DB_PREFIX_ . 'product
                    WHERE   id_product=' . (int)$product_id;
        return Db::getInstance()->ExecuteS($sql)[0];
    }

    public function getOrderState($params, $_)
    {
        $order_state_id = $params['state_id'];
        if (!$order_state_id) {
            return false;
        }

        // else, returns an OrderState object
        return (new OrderState($order_state_id, Configuration::get('PS_LANG_DEFAULT')))->name;
    }

    private function addressSplitter($address, $address2 = null, $address3 = null)
    {
        $result = array();
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

        return $result;
    }

    private function buildWuunderData($order_info)
    {
        $shippingAddress = new Address((int)$order_info['id_address_delivery']);

        // Get full address, strip enters/newlines etc
        $addressLine = trim(preg_replace('/\s+/', ' ', $shippingAddress->address1));

        // Splitt addres in 3 parts
        $addressParts = $this->addressSplitter($addressLine);
        $streetName = $addressParts['streetName'];
        $houseNumber = $addressParts['houseNumber'] . $addressParts['houseNumberSuffix'];

        $customerAdr = new \Wuunder\Api\Config\AddressConfig();

        $customerAdr->setEmailAddress($order_info['email']);
        $customerAdr->setFamilyName($shippingAddress->lastname);
        $customerAdr->setGivenName($shippingAddress->firstname);
        $customerAdr->setLocality($shippingAddress->city);
        $customerAdr->setStreetName($streetName);
        $customerAdr->setHouseNumber($houseNumber);
        $customerAdr->setZipCode($shippingAddress->postcode);
        $customerAdr->setPhoneNumber($shippingAddress->phone);
        $customerAdr->setCountry($order_info['iso_code']);

        $webshopAdr = new \Wuunder\Api\Config\AddressConfig();

        $webshopAdr->setEmailAddress(Configuration::get('email'));
        $webshopAdr->setFamilyName(Configuration::get('lastname'));
        $webshopAdr->setGivenName(Configuration::get('firstname'));
        $webshopAdr->setLocality(Configuration::get('city'));
        $webshopAdr->setStreetName(Configuration::get('streetname'));
        $webshopAdr->setHouseNumber(Configuration::get('housenumber'));
        $webshopAdr->setZipCode(Configuration::get('zipcode'));
        $webshopAdr->setPhoneNumber(Configuration::get('phonenumber'));
        $webshopAdr->setCountry(Configuration::get('country'));
        $webshopAdr->setBusiness(Configuration::get('company_name'));

        $orderAmountExclVat = (int)$order_info['total_products'] * 100;

        // Load product image for first ordered item
        $image = null;
        if (file_exists('../img/p/' . $order_info['id_product'] . '/' . $order_info['id_product'] . '-home_default.jpg')) {
            $image = base64_encode(Tools::file_get_contents('../img/p/' . $order_info['id_product'] . '/' . $order_info['id_product'] . '-home_default.jpg'));
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
        $product_height = ($height > 0) ? round($height * $dimension_product_factor) : null;

        $preferredServiceLevel = "";

        for ($i = 1; $i < 5; $i++) {
            if (Configuration::get('wuunderfilter' . $i . 'carrier') == $order_info['id_carrier']) {
                $preferredServiceLevel = Configuration::get('wuunderfilter' . $i . 'filter');
                break;
            }
        }

        $parcelshop_id = $this->getParcelshopIdForOrder($order_info['id_order']);

        $bookingConfig = new Wuunder\Api\Config\BookingConfig();

        $bookingConfig->setDescription($order_info['description']);
        $bookingConfig->setPicture($image);
        $bookingConfig->setKind(null);
        $bookingConfig->setValue($orderAmountExclVat);
        $bookingConfig->setLength($product_length);
        $bookingConfig->setWidth($product_width);
        $bookingConfig->setHeight($product_height);
        $bookingConfig->setWeight((int)$order_info['weight']);
        $bookingConfig->setCustomerReference($order_info['id_order']);
        $bookingConfig->setPreferredServiceLevel($preferredServiceLevel);
        $bookingConfig->setSource($this->sourceObj);
        $bookingConfig->setDeliveryAddress($customerAdr);
        $bookingConfig->setPickupAddress($webshopAdr);
        if ($parcelshop_id) {
            $bookingConfig->setParcelshopId($parcelshop_id);
        }
        return $bookingConfig;
    }

    private function requestBookingUrl($order_id)
    {
        $booking_url = $this->getBookingUrlForOrder($order_id);
        if (!$booking_url || empty($booking_url)) {
            // Fetch order
            $order = $this->getOrderInfo($order_id);

            // Get configuration
            $test_mode = Configuration::get('testmode');
            $booking_token = uniqid();
            $link = new Link();
            $path = explode('/', _PS_ADMIN_DIR_);
            $redirect_url = ((_PS_VERSION_ < '1.7') ? _PS_BASE_URL_ . __PS_BASE_URI__ . end($path) . "/" : "") . $link->getAdminLink('AdminWuunderConnector', true);
            $webhook_url = _PS_BASE_URL_ . __PS_BASE_URI__ . "index.php?fc=module&module=wuunderconnector&controller=wuunderwebhook&orderid=" . $order_id . "&wtoken=" . $booking_token;

            if ($test_mode == 1) {
                $apiKey = Configuration::get('test_api_key');
            } else {
                $apiKey = Configuration::get('live_api_key');
            }

            // Combine wuunder info and order data
            $bookingConfig = $this->buildWuunderData($order);
            $bookingConfig->setWebhookUrl($webhook_url);
            $bookingConfig->setRedirectUrl($redirect_url);

            $connector = new Wuunder\Connector($apiKey, $test_mode == 1);
            $booking = $connector->createBooking();
            //$booking->setBookingConfig($bookingConfig);
            $this->logger->logDebug($apiKey . " " . $test_mode);
            if ($bookingConfig->validate()) {
                $booking->setConfig($bookingConfig);
                $this->logger->logDebug("Going to fire for bookingurl");
                if ($booking->fire()) {
                    $url = $booking->getBookingResponse()->getBookingUrl();
                } else {
                    $this->logger->logDebug($booking->getBookingResponse()->getError());
                }
            } else {
                $this->logger->logDebug("Bookingconfig not complete");
            }
            $this->logger->logDebug("Handling response");
            $this->setBookingToken($order_id, $url, $booking_token);
            Tools::redirect($url);
        } else {
            $this->logger->logDebug("I'm in the else" . $booking_url);
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

        $test_mode = Configuration::get('testmode');

        if ($test_mode == 1) {
            $apiKey = Configuration::get('test_api_key');
        } else {
            $apiKey = Configuration::get('live_api_key');
        }

        if (empty($apiKey)) {
            $this->errors[] = Tools::displayError("Api Key is empty");
        }

        $link = new Link();
        $path = explode('/', _PS_ADMIN_DIR_);
        Context::getContext()->smarty->registerPlugin("function", "order_state", array($this, 'getOrderState'));
        Context::getContext()->smarty->assign(
            array(
            'version' => floatval(_PS_VERSION_),
            'order_info' => $order_info,
            'admin_url' => ((_PS_VERSION_ < '1.7') ? _PS_BASE_URL_ . __PS_BASE_URI__ . end($path) . "/" : "") . $link->getAdminLink('AdminWuunderConnector', true),)
        );
        $this->setTemplate('AdminWuunderConnector.tpl');
        parent::initContent();
    }
}
