<?php

namespace Wuunder\Api\Config;

class BookingConfig extends Config
{
    public function __construct()
    {
        parent::__construct();
        $this->defaultFields = array(
            "picture" => null,
            "source" => array(
                "product" => "connector-php"
            ),
            "customer_reference" => null,
            "personal_message" => null
        );
        $this->requiredFields = array(
            "redirect_url",
            "webhook_url"
        );
    }
}
