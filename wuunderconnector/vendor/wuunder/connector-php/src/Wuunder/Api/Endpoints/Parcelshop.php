<?php

namespace Wuunder\Api\Endpoints;

use Wuunder\Api\Config\ParcelshopConfig;
use Wuunder\Api\Environment;
use Wuunder\Api\Key;
use Wuunder\Api\ParcelshopApiResponse;
use Wuunder\Http\GetRequest;
use Wuunder\Util\Helper;

class Parcelshop
{
    private $config;
    private $apiKey;
    private $apiEnvironment;
    private $parcelshopResponse;
    private $logger;

    public function __construct(Key $apiKey, Environment $apiEnvironment)
    {
        $this->config = new ParcelshopConfig();
        $this->apiKey = $apiKey;
        $this->apiEnvironment = $apiEnvironment;
        $this->logger = Helper::getInstance();
    }

    /**
     * Set data to send to API
     *
     * @param ParcelshopConfig $config Config of the request
     * @internal param mixed $data JSON encoded
     */
    public function setConfig(ParcelshopConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Return BookingConfig object of current booking
     *
     * @return ParcelshopConfig
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
        $parcelshopRequest = new GetRequest($this->apiEnvironment->getStageBaseUrl() . "/parcelshops/" . $this->config->get("id"),
            $this->apiKey->getApiKey());
        try {
            $parcelshopRequest->send();
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
        $body = null;
        $header = null;
        $error = null;

        if (isset($parcelshopRequest->getResponseHeaders()["http_code"])
            && strpos($parcelshopRequest->getResponseHeaders()["http_code"], "200 OK") !== false
        ) {
            $body = $parcelshopRequest->getBody();
            $header = $parcelshopRequest->getResponseHeaders();
        } else {
            $error = $parcelshopRequest->getResponse();
        }
        $this->parcelshopResponse = new ParcelshopApiResponse($header, $body, $error);

        return is_null($error);
    }

    /**
     * Returns parcelshop response object
     *
     * @return ParcelshopApiResponse
     */
    public function getParcelshopResponse()
    {
        return $this->parcelshopResponse;
    }
}
