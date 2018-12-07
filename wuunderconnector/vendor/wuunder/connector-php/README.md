# connector-php
PHP connector for Wuunder API

Installation:  
`composer require wuunder/connector-php`

Set-up connection:  
`$connector = new Wuunder\Connector("API_KEY");`

Create booking:
```php
$booking = $connector->createBooking();

$bookingConfig = new Wuunder\Api\Config\BookingConfig();
$bookingConfig->setWebhookUrl("url");
$bookingConfig->setRedirectUrl("url");

if ($bookingConfig->validate()) {
    $booking->setConfig($bookingConfig);

    if ($booking->fire()) {
        var_dump($booking->getBookingResponse()->getBookingUrl());
    } else {
        var_dump($booking->getBookingResponse()->getError());
    }
} else {
    print("Bookingconfig not valid");
}
```

Create shipment:
```php
$shipment = $connector->createShipment();

$shipmentConfig = new \Wuunder\Api\Config\ShipmentConfig();
$shipmentConfig->setDescription("Test");
$shipmentConfig->setKind("package");
$shipmentConfig->setValue(200);
$shipmentConfig->setLength(10);
$shipmentConfig->setWidth(10);
$shipmentConfig->setHeight(10);
$shipmentConfig->setWeight(210);
$shipmentConfig->setPreferredServiceLevel("cheapest");

$deliveryAddress = new \Wuunder\Api\Config\AddressConfig();
$deliveryAddress->setEmailAddress("email");
$deliveryAddress->setFamilyName("Lastname");
$deliveryAddress->setGivenName("Firstname");
$deliveryAddress->setLocality("City");
$deliveryAddress->setStreetName("Street");
$deliveryAddress->setHouseNumber("Number");
$deliveryAddress->setZipCode("Zipcode");
$deliveryAddress->setCountry("NL");

$shipmentConfig->setDeliveryAddress($deliveryAddress);

$pickupAddress = new \Wuunder\Api\Config\AddressConfig();
$pickupAddress->setEmailAddress("email");
$pickupAddress->setFamilyName("Lastname");
$pickupAddress->setGivenName("Firstname");
$pickupAddress->setLocality("City");
$pickupAddress->setStreetName("Street");
$pickupAddress->setHouseNumber("Number");
$pickupAddress->setZipCode("Zipcode");
$pickupAddress->setCountry("NL");

$shipmentConfig->setPickupAddress($pickupAddress);

if ($shipmentConfig->validate()) {
    $shipment->setConfig($shipmentConfig);

    if ($shipment->fire()) {
        var_dump($shipment->getShipmentResponse()->getShipmentData());
    } else {
        var_dump($shipment->getShipmentResponse()->getError());
    }
} else {
    print("ShipmentConfig not valid");
}
```

Get Parcelshops in neighbourhoud by address:  
```php
$parcelshopsRequest = $connector->getParcelshopsByAddress();

$parcelshopsConfig = new \Wuunder\Api\Config\ParcelshopsConfig();
$parcelshopsConfig->setProviders(array("CARRIERCODE"));
$parcelshopsConfig->setAddress("address");
$parcelshopsConfig->setLimit(40);

if ($parcelshopsConfig->validate()) {
    $parcelshopsRequest->setConfig($parcelshopsConfig);

    if ($parcelshopsRequest->fire()) {
        var_dump(json_encode($parcelshopsRequest->getParcelshopsResponse()->getParcelshopsData()));
    } else {
        var_dump($parcelshopsRequest->getParcelshopsResponse()->getError());
    }
} else {
    print("ParcelshopsConfig not valid");
}
```

Get info of a specific parcelshop:  
```php
$parcelshopRequest = $connector->getParcelshopById();

$parcelshopConfig = new \Wuunder\Api\Config\ParcelshopConfig();
$parcelshopConfig->setId("id");

if ($parcelshopConfig->validate()) {
    $parcelshopRequest->setConfig($parcelshopConfig);

    if ($parcelshopRequest->fire()) {
        var_dump(json_encode($parcelshopRequest->getParcelshopResponse()->getParcelshopData()));
    } else {
        var_dump($parcelshopRequest->getParcelshopResponse()->getError());
    }
} else {
    print("ParcelshopConfig not valid");
}

```


# Wuunder
Wuunder offers an API for sending & receiving your parcel, pallet and document the most easy way. Ship with carriers like DHL, DPD,  GLS and PostNL, etc. Only available to ship within, from and to the Netherlands.

- Save time preparing your orders and send all order- & shipping details fully automated to all carriers;
- Select how you want to ship your documents, parcels and pallets: same-day, next-day or slower;
- Use one of the >20 carriers (use our or even your own carrier contract);
- Your shipping address and phone numbers will be validated automatically to avoid unnecessary return shipments;
- Print one or more shipping labels at once;
- Also organize a return or drop-shipment easily and your customer, supplier or warehouse-employee will receive the label per email;
- A pick-up is arranged fully automated (at your, your customers or your suppliers location) or select a regular pick-up with one or more carriers;
- You can track all shipments (from different carriers) in one handy dashboard (track-and-trace);
- Inform the receiver directly via notification or e-mail (option). Your product pictures and personal chat message are used in the communication with the receiver;
- You will increase your revenue, using the chat-option with your customer (option);
- With one click you arrange a return shipment, including a pick-up or parcelshop drop-off;
- Wuunder offers pro-active tracking of your shipments. We take action and call the carrier, receiver or supplier for you, when there are any delays, pick-up issues, etc. It’s that easy!

Our shipping API has a staging and production environment. This allows you to test all aspects of the module before you go live. Please contact Wuunder if you want to use the shipping API and we'll send the API keys asap:  Info@WeAreWuunder.com