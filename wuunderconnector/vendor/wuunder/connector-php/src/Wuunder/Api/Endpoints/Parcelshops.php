<?php

namespace Wuunder\Api\Endpoints;

use Wuunder\Api\Config\ParcelshopsConfig;
use Wuunder\Api\Environment;
use Wuunder\Api\Key;
use Wuunder\Api\ParcelshopsApiResponse;
use Wuunder\Http\GetRequest;

class Parcelshops
{
    private $config;
    private $apiKey;
    private $apiEnvironment;
    private $parcelshopsResponse;

    public function __construct(Key $apiKey, Environment $apiEnvironment)
    {
        $this->config = new ParcelshopsConfig();
        $this->apiKey = $apiKey;
        $this->apiEnvironment = $apiEnvironment;
    }

    /**
     * Set data to send to API
     *
     * @param ParcelshopsConfig $config
     * @internal param mixed $data JSON encoded
     */
    public function setConfig(ParcelshopsConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Return BookingConfig object of current booking
     *
     * @return ParcelshopsConfig
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
        $parcelshopsRequest = new GetRequest($this->apiEnvironment->getStageBaseUrl() . "/parcelshops_by_address" . $this->config->toGetParameters(),
            $this->apiKey->getApiKey());
        try {
            $parcelshopsRequest->send();
        } catch(Exception $e) {
            $this->logger->log($e);
        }

        $body = null;
        $header = null;
        $error = null;

        if (isset($parcelshopsRequest->getResponseHeaders()["http_code"]) && strpos($parcelshopsRequest->getResponseHeaders()["http_code"], "200 OK") !== false) {
            $body = $parcelshopsRequest->getBody();
            $header = $parcelshopsRequest->getResponseHeaders();
        } else {
            $error = $parcelshopsRequest->getResponse();
        }
        $this->parcelshopsResponse = new ParcelshopsApiResponse($header, $body, $error);

        return is_null($error);
    }

    /**
     * Returns parcelshop models
     *
     * @return ParcelshopsApiResponse
     */
    public function getParcelshopsResponse()
    {
        return $this->parcelshopsResponse;
    }
}
