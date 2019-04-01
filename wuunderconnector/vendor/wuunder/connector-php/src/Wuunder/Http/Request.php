<?php

namespace Wuunder\Http;

abstract class Request
{

    protected $url;
    protected $apiKey;
    protected $result;
    protected $headerSize;


    public function __construct($url, $apiKey)
    {
        $this->url = $url;
        $this->apiKey = $apiKey;
    }

    abstract protected function send();

    /**
     * Returns the whole response from a response
     *
     * @return $result
     */
    public function getResponse()
    {
        return $this->result;
    }

    /**
     * Returns the response body from a response
     *
     * @return $result (body)
     */
    public function getBody()
    {
        return substr($this->result, $this->headerSize);
    }

    /**
     * Returns the headers from a response
     *
     * @return $headers
     */
    public function getResponseHeaders()
    {
        $headers = array();

        $header_text = substr($this->result, 0, $this->headerSize);

        foreach (explode("\r\n", $header_text) as $i => $line)
            if (strlen($line) > 4 && substr($line, 0, 4) === "HTTP") {
                $headers['http_code'] = $line;
            } else {
                list ($key, $value) = array_pad(explode(': ', $line, 2), 2, null);
                $headers[$key] = $value;
            }

        return $headers;
    }
}
