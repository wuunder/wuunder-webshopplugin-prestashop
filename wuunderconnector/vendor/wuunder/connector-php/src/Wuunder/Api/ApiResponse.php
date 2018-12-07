<?php

namespace Wuunder\Api;

class ApiResponse {

    protected $error;
    private $header;
    private $body;

    public function __construct($header, $body, $error)
    {
        $this->header = $header;
        $this->body = $body;
        $this->error = $error;
    }

    /**
    * @return mixed
    */
    public function getError() {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }
}
