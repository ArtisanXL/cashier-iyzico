<?php

namespace ArtisanXL\CashierIyzico;

use Iyzipay\Model\Address as IyzicoAddress;

final readonly class Address
{
    public function __construct(
        public string $address,
        public string $contactName,
        public string $city,
        public string $country,
        public string $zipCode,
    ) {}

    public function toIyzicoAddress(): IyzicoAddress
    {
        $address = new IyzicoAddress;
        $address->setAddress($this->address);
        $address->setContactName($this->contactName);
        $address->setCity($this->city);
        $address->setCountry($this->country);
        $address->setZipCode($this->zipCode);

        return $address;
    }
}
