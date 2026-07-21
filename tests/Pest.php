<?php

use ArtisanXL\CashierIyzico\Address;
use ArtisanXL\CashierIyzico\Buyer;
use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use ArtisanXL\CashierIyzico\Tests\Fixtures\FakeIyzicoGateway;
use ArtisanXL\CashierIyzico\Tests\Fixtures\User;
use ArtisanXL\CashierIyzico\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function bindFakeGateway(): FakeIyzicoGateway
{
    $fake = new FakeIyzicoGateway;

    app()->instance(IyzicoGatewayContract::class, $fake);

    return $fake;
}

function createBillableUser(): User
{
    return User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('secret'),
    ]);
}

function testBuyer(): Buyer
{
    return new Buyer(
        name: 'John',
        surname: 'Doe',
        identityNumber: '74300864791',
        email: 'john@example.com',
        gsmNumber: '+905350000000',
        registrationAddress: 'Nidakule Goztepe, Merdivenkoy Mah. Bora Sok. No:1',
        city: 'Istanbul',
        country: 'Turkey',
        zipCode: '34732',
        ip: '85.34.78.112',
    );
}

function testAddress(): Address
{
    return new Address(
        address: 'Nidakule Goztepe, Merdivenkoy Mah. Bora Sok. No:1',
        contactName: 'John Doe',
        city: 'Istanbul',
        country: 'Turkey',
        zipCode: '34732',
    );
}

/**
 * @return array<string, mixed>
 */
function testPaymentCard(): array
{
    return [
        'card_holder_name' => 'John Doe',
        'card_number' => '5528790000000008',
        'expire_month' => '12',
        'expire_year' => '2030',
        'cvc' => '123',
    ];
}
