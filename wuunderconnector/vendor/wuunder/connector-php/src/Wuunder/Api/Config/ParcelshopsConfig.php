<?php

namespace Wuunder\Api\Config;

class ParcelshopsConfig extends Config
{
    private $fieldTypes;

    public function __construct()
    {
        parent::__construct();
        $this->requiredFields = array(
            "providers",
            "address"
        );
        $this->fieldTypes = array(
            "providers" => "list",
            "address" => "string"
        );
    }

    /**
    * 
    *
    * @return $parameterString
    */
    public function toGetParameters()
    {
        $parameterString = "";
        foreach ($this->setFields as $key => $value) {
            $type = (isset($this->fieldTypes[$key]) ? $this->fieldTypes[$key] : "");
            switch ($type) {
                case "list":
                    if (is_array($value)) {
                        $parameterString .= "&" . $key . "[]=" . implode("&" . $key . "[]=", $value);
                    } else {
                        $parameterString .= "&" . $key . "[]=" . $value;
                    }
                    break;
                case "integer":
                    $parameterString .= "&" . $key . "=" . $value;
                    break;
                default:
                    $parameterString .= "&" . $key . "=" . urlencode($value);
                    break;
            }
        }

        if (substr($parameterString, 0 ,1) == "&") {
            $parameterString = "?" . substr($parameterString, 1);
        }
        return $parameterString;
    }
}
