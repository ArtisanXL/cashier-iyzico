<?php

use ArtisanXL\CashierIyzico\Exceptions\IncompletePayment;
use ArtisanXL\CashierIyzico\Exceptions\InvalidCustomer;
use ArtisanXL\CashierIyzico\Exceptions\InvalidSubscription;
use ArtisanXL\CashierIyzico\Exceptions\IyzicoException;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate')->run();
});

it('throws InvalidCustomer when the billable has no iyzico customer id', function () {
    $user = createBillableUser();

    expect(fn () => $user->iyzicoIdOrFail())
        ->toThrow(InvalidCustomer::class);
});

it('makes InvalidCustomer catchable as the package base exception', function () {
    expect(new InvalidCustomer('missing'))->toBeInstanceOf(IyzicoException::class);
});

it('throws InvalidSubscription when canceling an already-canceled subscription', function () {
    bindFakeGateway();

    $user = createBillableUser();
    $subscription = $user->newSubscription('default', 'prod_9', 'plan_9')
        ->create(testBuyer(), testAddress(), testPaymentCard());

    $subscription->cancel();

    expect(fn () => $subscription->cancel())
        ->toThrow(InvalidSubscription::class);
});

it('throws InvalidSubscription when swapping an ended subscription', function () {
    bindFakeGateway();

    $user = createBillableUser();
    $subscription = $user->newSubscription('default', 'prod_9', 'plan_9')
        ->create(testBuyer(), testAddress(), testPaymentCard());

    $subscription->cancel();

    expect(fn () => $subscription->swap('plan_10'))
        ->toThrow(InvalidSubscription::class);
});

it('throws InvalidSubscription when resuming a subscription that is not canceled', function () {
    bindFakeGateway();

    $user = createBillableUser();
    $subscription = $user->newSubscription('default', 'prod_9', 'plan_9')
        ->create(testBuyer(), testAddress(), testPaymentCard());

    expect(fn () => $subscription->resume())
        ->toThrow(InvalidSubscription::class);
});

it('throws IncompletePayment for an unsuccessful direct charge while still recording the transaction', function () {
    $fake = bindFakeGateway();
    $fake->paymentId = 'iyz_pay_failed';
    $fake->paymentStatus = 'FAILURE';

    $user = createBillableUser();

    try {
        $user->charge(10000, testBuyer(), testAddress(), [
            'currency' => 'TRY',
            'payment_card' => testPaymentCard(),
        ]);

        $this->fail('Expected IncompletePayment to be thrown.');
    } catch (IncompletePayment $exception) {
        expect($exception->payment()->status())->toBe('FAILURE')
            ->and($exception->payment()->paymentId())->toBe('iyz_pay_failed');
    }

    $this->assertDatabaseHas('transactions', [
        'billable_id' => $user->getKey(),
        'billable_type' => $user->getMorphClass(),
        'iyzico_id' => 'iyz_pay_failed',
        'status' => 'FAILURE',
        'amount' => 10000,
    ]);
});
