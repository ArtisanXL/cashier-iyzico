<?php

namespace ArtisanXL\CashierIyzico;

use Iyzipay\Model\Buyer as IyzicoBuyer;

final readonly class Buyer
{
    public function __construct(
        public string $name,
        public string $surname,
        public string $identityNumber,
        public string $email,
        public string $gsmNumber,
        public string $registrationAddress,
        public string $city,
        public string $country,
        public string $zipCode,
        public string $ip,
        public ?string $id = null,
    ) {}

    public function toIyzicoBuyer(): IyzicoBuyer
    {
        $buyer = new IyzicoBuyer;
        $buyer->setId($this->id ?? $this->identityNumber);
        $buyer->setName($this->name);
        $buyer->setSurname($this->surname);
        $buyer->setIdentityNumber($this->identityNumber);
        $buyer->setEmail($this->email);
        $buyer->setGsmNumber($this->gsmNumber);
        $buyer->setRegistrationAddress($this->registrationAddress);
        $buyer->setCity($this->city);
        $buyer->setCountry($this->country);
        $buyer->setZipCode($this->zipCode);
        $buyer->setIp($this->ip);

        return $buyer;
    }
}
