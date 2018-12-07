<?php

namespace Wuunder\Model;

class ShipmentModel extends Model
{
    public function __construct($data)
    {
        parent::__construct();
        $this->setKeys(array(
            "width",
            "height",
            "length",
            "weight",
            "value",
            "kind",
            "track_and_trace_url",
            "track_and_trace_details" => array(
                "track_and_trace_code",
                "carrier_name",
                "carrier_code"
            ),
            "status",
            "picture_url",
            "pickup_address" => array(
                "zip_code",
                "type",
                "street_name",
                "street_address",
                "state",
                "locality",
                "phone_number",
                "house_number",
                "email_address",
                "id",
                "given_name",
                "family_name",
                "country_name",
                "city",
                "country",
                "chamber_of_commerce_number",
                "business"
            ),
            "personal_message",
            "parcelshop_id",
            "name",
            "label_url",
            "is_return",
            "id",
            "drop_off",
            "description",
            "delivery_address" => array(
                "zip_code",
                "type",
                "street_name",
                "street_address",
                "state",
                "locality",
                "phone_number",
                "house_number",
                "email_address",
                "id",
                "given_name",
                "family_name",
                "country_name",
                "city",
                "country",
                "chamber_of_commerce_number",
                "business"
            ),
            "customer_reference"
        ));

        $this->importData($data);
    }
}