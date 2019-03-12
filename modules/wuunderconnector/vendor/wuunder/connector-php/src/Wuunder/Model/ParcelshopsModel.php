<?php

namespace Wuunder\Model;

class ParcelshopsModel extends Model
{
    public function __construct($data)
    {
        parent::__construct();
        $this->setKeys(array(
            "parcelshops" => array(
                ParcelshopModel::getStructure()
            ),
            "address" => array(
                "zip_code",
                "street_name",
                "state",
                "house_number",
                "city",
                "phone_number",
                "email_address",
                "country_name",
                "alpha2"
            ),
            "location" => array(
                "lng",
                "lat"
            )
        ));

        $this->importData($data, array("weekday"));
    }
}