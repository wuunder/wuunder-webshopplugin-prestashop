<?php

if (!defined('_PS_VERSION_'))
    exit;

class wuunderconnectorParcelshopModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->context = Context::getContext();
        $this->ssl = false;
        $this->logger = new FileLogger(0); //0 == debug level, logDebug() won’t work without this.
        $this->logger->setFilename(_PS_ROOT_DIR_ . ((_PS_VERSION_ < '1.7') ? "/log/wuunder.log" : "/app/logs/wuunder.log"));
        $this->ajax = true;
    }

    public function initContent()
    {
        Parent::initContent();
        if (isset($_REQUEST['getAddress'])) {
            $this->getCheckoutAddress();
        }

        if (isset($_REQUEST['setParcelshopId'])) {
            $this->setParcelshopId();
        }
        
    }

    private function getCheckoutAddress()
    {
        $addressId = $_REQUEST['addressId'];
        $address = new Address(intval($addressId));
        header('Content-Type: application/json');
        die(Tools::jsonEncode($address));
    }

    private function setParcelshopId()
    {
        if(Tools::getValue('parcelshopId')) {
            $parcelshopId = Tools::getValue('parcelshopId');
            $this->context->cookie->parcelId = $parcelshopId;
            $address = $this->getParcelshopAddress($parcelshopId);
            die(Tools::jsonEncode($address));
        }
        return null;
    }

    private function getParcelshopAddress($id) {
        $shipping_address = null;
    
        if(empty($id)) {
            echo null;
        } else {
            $status = Configuration::get('testmode');
            $apiKey = ($status == 0 ? Configuration::get('live_api_key') : Configuration::get('test_api_key'));
    
            $connector = new Wuunder\Connector($apiKey);
            $connector->setLanguage("NL");
            $parcelshopRequest = $connector->getParcelshopById();
            $parcelshopConfig = new \Wuunder\Api\Config\ParcelshopConfig();
    
            $parcelshopConfig->setId($id);
    
            if ($parcelshopConfig->validate()) {
                $parcelshopRequest->setConfig($parcelshopConfig);
                if ($parcelshopRequest->fire()) {
                    $parcelshop = $parcelshopRequest->getParcelshopResponse()->getParcelshopData();
                } else {
                    echo 'error';
                    var_dump($parcelshopRequest->getParcelshopResponse()->getError());
                }
            } else {
                $parcelshop = "ParcelshopsConfig not complete";
            }
            return $parcelshop;
        }
    
        return null;
    }

}

