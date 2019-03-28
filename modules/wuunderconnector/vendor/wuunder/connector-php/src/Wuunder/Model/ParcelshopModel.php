<?php

namespace Wuunder\Model;

class ParcelshopModel extends Model
{
    static private $modelStructure = array(
        "provider",
        "parcelshop_id",
        "opening_hours" => array(
            array(
                "weekday",
                "open_morning",
                "open_afternoon",
                "close_morning",
                "close_afternoon"
            )
        ),
        "longitude",
        "latitude",
        "id",
        "homepage",
        "distance",
        "company_name",
        "carrier_name",
        "address" => array(
            "zip_code",
            "street_name",
            "state",
            "phone_number",
            "house_number",
            "email_address",
            "country_name",
            "city",
            "alpha2"
        )
    );

    public function __construct($data)
    {
        parent::__construct();
        $this->setKeys(self::$modelStructure);

        $this->importData($data, array("weekday"));
    }

    final static function getStructure() {
        return self::$modelStructure;
    }
}