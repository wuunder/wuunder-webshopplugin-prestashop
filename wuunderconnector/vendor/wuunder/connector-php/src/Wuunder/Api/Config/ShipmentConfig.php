<?php

namespace Wuunder\Api\Config;

class ShipmentConfig extends Config
{
    public function __construct()
    {
        parent::__construct();
        $this->defaultFields = array(
            "picture" => null,
            "customer_reference" => null,
            "personal_message" => null
        );
        $this->requiredFields = array(
            "description",
            "value",
            "kind",
            "length",
            "width",
            "height",
            "weight",
            "delivery_address",
            "pickup_address",
            "preferred_service_level"
        );
    }
}