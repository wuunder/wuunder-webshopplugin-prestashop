<?php

namespace Wuunder\Api;

use Wuunder\Model\ParcelshopsModel;

class ParcelshopsApiResponse extends ApiResponse {

    public function __construct($header, $body, $error)
    {
        parent::__construct($header, $body, $error);
    }

    /**
     * Returns parcelshops model
     *
     * @return ParcelshopsModel
     */
    public function getParcelshopsData()
    {
        return new ParcelshopsModel($this->getBody());
    }
}