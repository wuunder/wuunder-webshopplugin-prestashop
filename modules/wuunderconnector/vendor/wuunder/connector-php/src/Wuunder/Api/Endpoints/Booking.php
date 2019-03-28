<?php

namespace Wuunder\Api\Endpoints;

use Wuunder\Api\BookingApiResponse;
use Wuunder\Api\Config\BookingConfig;
use Wuunder\Api\Environment;
use Wuunder\Api\Key;
use Wuunder\Http\PostRequest;
use Wuunder\Util\Helper;

class Booking
{
    private $config;
    private $apiKey;
    private $apiEnvironment;
    private $bookingResponse;
    private $logger;

    public function __construct(Key $apiKey, Environment $apiEnvironment)
    {
        $this->config = new BookingConfig();
        $this->apiKey = $apiKey;
        $this->apiEnvironment = $apiEnvironment;
        $this->logger = Helper::getInstance();
    }

    /**
     * Set data to send to API
     *
     * @param BookingConfig $config
     * @internal param mixed $data JSON encoded
     */
    public function setConfig(BookingConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Return BookingConfig object of current booking
     *
     * @return BookingConfig
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
        $bookingRequest = new PostRequest($this->apiEnvironment->getStageBaseUrl() . "/bookings",
            $this->apiKey->getApiKey(), json_encode($this->config));
        try {
          $bookingRequest->send();
        } catch(Exception $e) {
            $this->logger->log($e);
        }

        $body = null;
        $header = null;
        $error = null;

        if (isset($bookingRequest->getResponseHeaders()["location"])) {
            $header = $bookingRequest->getResponseHeaders();
        } else {
            $error = $bookingRequest->getResponse();
        }
        $this->bookingResponse = new BookingApiResponse($header, $body, $error);

        return is_null($error);
    }

    /**
     * Returns booking response object
     *
     * @return mixed
     */
    public function getBookingResponse()
    {
        return $this->bookingResponse;
    }
}
