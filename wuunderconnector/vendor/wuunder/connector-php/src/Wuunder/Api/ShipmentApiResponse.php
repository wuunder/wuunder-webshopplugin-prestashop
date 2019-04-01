<?php

namespace Wuunder\Api;

use Wuunder\Model\ShipmentModel;

class ShipmentApiResponse extends ApiResponse {

    public function __construct($header, $body, $error)
    {
        parent::__construct($header, $body, $error);
    }

    /**
     * Returns booking url
     *
     * @return ShipmentModel
     */
    public function getShipmentData()
    {
        return new ShipmentModel($this->getBody());
    }
}
