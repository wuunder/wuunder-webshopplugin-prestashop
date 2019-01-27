<?php

namespace Wuunder\Api;

use Wuunder\Model\ParcelshopModel;

class ParcelshopApiResponse extends ApiResponse {

    public function __construct($header, $body, $error)
    {
        parent::__construct($header, $body, $error);
    }

    /**
     * Returns parcelshop data
     *
     * @return ParcelshopModel
     */
    public function getParcelshopData()
    {
        return new ParcelshopModel($this->getBody());
    }
}