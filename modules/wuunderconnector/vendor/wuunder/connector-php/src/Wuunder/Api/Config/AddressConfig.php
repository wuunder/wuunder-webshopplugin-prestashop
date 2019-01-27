<?php

namespace Wuunder\Api\Config;

class AddressConfig extends Config
{
    public function __construct()
    {
        parent::__construct();
        $this->requiredFields = array(
            "email_address",
            "family_name",
            "given_name",
            "locality",
            "phone_number",
            "street_name",
            "house_number",
            "zip_code",
            "country"
        );
    }
}