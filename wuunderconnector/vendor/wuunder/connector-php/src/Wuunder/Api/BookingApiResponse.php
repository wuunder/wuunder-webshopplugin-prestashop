<?php

namespace Wuunder\Api;

class BookingApiResponse extends ApiResponse {

    public function __construct($header, $body, $error)
    {
        parent::__construct($header, $body, $error);
    }

    /**
     * Returns booking url
     *
     * @return mixed
     */
    public function getBookingUrl()
    {
        return $this->getHeader()['location'];
    }
}