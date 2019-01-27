<?php

namespace Wuunder\Api\Endpoints;

use Wuunder\Api\Config\ShipmentConfig;
use Wuunder\Api\Environment;
use Wuunder\Api\Key;
use Wuunder\Api\ShipmentApiResponse;
use Wuunder\Http\PostRequest;
use Wuunder\Util\Helper;

class Shipment {

    private $config;
    private $apiKey;
    private $apiEnvironment;
    private $shipmentResponse;
    private $logger;

    public function __construct(Key $apiKey, Environment $apiEnvironment)
    {
        $this->config = new ShipmentConfig();
        $this->apiKey = $apiKey;
        $this->apiEnvironment = $apiEnvironment;
        $this->logger = Helper::getInstance();
    }

    /**
     * Set data to send to API
     *
     * @param ShipmentConfig $config
     * @internal param mixed $data JSON encoded
     */
    public function setConfig(ShipmentConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Return BookingConfig object of current booking
     *
     * @return ShipmentConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Fires the request and handles the result.
     *
     * @return bool
     */
    public function fire()
    {
        $shipmentRequest = new PostRequest($this->apiEnvironment->getStageBaseUrl() . "/shipments",
            $this->apiKey->getApiKey(), json_encode($this->config));
        try {
            $shipmentRequest->send();
        } catch(Exception $e) {
            $this->logger->log($e);
        }

        $body = null;
        $header = null;
        $error = null;

        if (isset($shipmentRequest->getResponseHeaders()["http_code"]) && strpos($shipmentRequest->getResponseHeaders()["http_code"], "201 Created") !== false) {
            $body = $shipmentRequest->getBody();
            $header = $shipmentRequest->getResponseHeaders();
        } else {
            $error = $shipmentRequest->getResponse();
        }
        $this->shipmentResponse = new ShipmentApiResponse($header, $body, $error);

        return is_null($error);
    }

    /**
     * Returns shipment response object
     *
     * @return mixed
     */
    public function getShipmentResponse()
    {
        return $this->shipmentResponse;
    }
}
