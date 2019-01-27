<?php

namespace Wuunder;

use Wuunder\Api\Key;
use Wuunder\Api\Environment;
use Wuunder\Api\Endpoints\Booking;
use Wuunder\Api\Endpoints\Shipment;
use Wuunder\Api\Endpoints\Parcelshops;
use Wuunder\Api\Endpoints\Parcelshop;
use Wuunder\Util\Helper;


class Connector
{

    private $apiKey;
    private $apiEnvironment;
    private $helper;

    public function __construct($apiKey, $isStaging = true)
    {
        $this->apiKey = new Key($apiKey);
        $this->apiEnvironment = new Environment($isStaging ? "staging" : "production");
        $this->helper = Helper::getInstance();
    }

    /**
     * Creates a new Booking
     *
     * @return Booking
     */
    public function createBooking() {
        return new Booking($this->apiKey, $this->apiEnvironment);
    }

    /**
     * Creates a new Shipment
     *
     * @return Shipment
     */
    public function createShipment() {
        return new Shipment($this->apiKey, $this->apiEnvironment);
    }

    /**
     * Creates a new Parcelshops
     *
     * @return Parcelshops
     */
    public function getParcelshopsByAddress() {
        return new Parcelshops($this->apiKey, $this->apiEnvironment);
    }

    public function getParcelshopById() {
        return new Parcelshop($this->apiKey, $this->apiEnvironment);
    }

    /**
    * Creates the logger functionality in Helper
    *
    */
    public function setLogger($loggerClass, $loggerFunc) {

        if(empty($loggerClass)) {
            $this->helper->setLogger($loggerFunc);
        } else {
            $this->helper->setLogger(array($loggerClass, $loggerFunc));
        }
    }

    /**
     * Sets user language, for translations
     *
     * @param $lang
     */
    public function setLanguage($lang) {
        $this->helper->setTranslationLang($lang);
    }

    /**
    * Logs the input parameter
    *
    * @param $logText
    */
    public function log($logText) {
        $helper = Helper::getInstance();
        $helper->log($logText);
    }

}
