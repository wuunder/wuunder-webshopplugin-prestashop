<?php

namespace Wuunder\Http;

use Wuunder\Http\Request;
use Wuunder\Util\Helper;

class GetRequest extends Request {

    private $logger;

    public function __construct($url, $apiKey)
    {
        parent::__construct($url, $apiKey);
        $this->logger = Helper::getInstance();
    }

    /**
    * Sends a get request and recieves results
    *
    */
    public function send()
    {
        $cc = curl_init($this->url);
        $this->logger->log("API connection established");

        curl_setopt($cc, CURLOPT_HTTPHEADER,
            array('Authorization: Bearer ' . $this->apiKey, 'Content-type: application/json'));
        curl_setopt($cc, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cc, CURLOPT_VERBOSE, 0);
        curl_setopt($cc, CURLOPT_HEADER, 1);

        // Execute the cURL, fetch the XML
        $result = curl_exec($cc);
        $this->result = $result;
        $this->headerSize = curl_getinfo($cc, CURLINFO_HEADER_SIZE);

        // Close connection
        curl_close($cc);
    }
}
