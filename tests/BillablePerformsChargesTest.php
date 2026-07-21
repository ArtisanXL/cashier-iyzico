<?php

use ArtisanXL\CashierIyzico\Payment;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate')->run();
});

it('persists a transaction row with amount, currency and status on charge', function () {
    $fake = bindFakeGateway();
    $fake->paymentId = 'iyz_pay_777';
    $fake->paymentStatus = 'SUCCESS';

    $user = createBillableUser();

    $payment = $user->charge(10000, testBuyer(), testAddress(), [
        'currency' => 'TRY',
        'payment_card' => testPaymentCard(),
    ]);

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->successful())->toBeTrue()
        ->and($payment->requiresAction())->toBeFalse()
        ->and($payment->paymentId())->toBe('iyz_pay_777');

    $this->assertDatabaseHas('transactions', [
        'billable_id' => $user->getKey(),
        'billable_type' => $user->getMorphClass(),
        'iyzico_id' => 'iyz_pay_777',
        'status' => 'SUCCESS',
        'amount' => 10000,
        'currency' => 'TRY',
    ]);

    expect($fake->callsTo('charge'))->toHaveCount(1)
        ->and($fake->callsTo('charge')[0]['amount'])->toBe(10000);
});

it('initializes a 3-D Secure charge and records a pending transaction', function () {
    $fake = bindFakeGateway();
    $fake->paymentId = 'iyz_pay_3ds';
    $fake->threeDSHtmlContent = '<html>3ds-form</html>';

    $user = createBillableUser();

    $payment = $user->charge(5000, testBuyer(), testAddress(), [
        '3ds' => true,
        'callback_url' => 'https://example.test/iyzico/callback',
        'currency' => 'TRY',
        'payment_card' => testPaymentCard(),
    ]);

    expect($payment->requiresAction())->toBeTrue()
        ->and($payment->status())->toBe('pending')
        ->and($payment->threeDSHtmlContent())->toBe('<html>3ds-form</html>')
        ->and($payment->paymentId())->toBe('iyz_pay_3ds');

    $this->assertDatabaseHas('transactions', [
        'iyzico_id' => 'iyz_pay_3ds',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'TRY',
    ]);

    $threeDsCalls = $fake->callsTo('initializeThreedsCharge');

    expect($threeDsCalls)->toHaveCount(1)
        ->and($threeDsCalls[0]['amount'])->toBe(5000)
        ->and($threeDsCalls[0]['callbackUrl'])->toBe('https://example.test/iyzico/callback')
        ->and($fake->callsTo('charge'))->toHaveCount(0);
});

it('refunds a recorded transaction through the gateway using the payment transaction id, not the payment id', function () {
    $fake = bindFakeGateway();
    $fake->paymentId = 'iyz_pay_refund';
    $fake->paymentTransactionId = 'iyz_item_txn_refund';

    $user = createBillableUser();

    $user->charge(4200, testBuyer(), testAddress(), [
        'currency' => 'TRY',
        'payment_card' => testPaymentCard(),
    ]);

    $transaction = $user->refund('iyz_pay_refund');

    expect($transaction->status)->toBe('refunded')
        ->and($transaction->iyzico_payment_transaction_id)->toBe('iyz_item_txn_refund')
        ->and($fake->callsTo('refund'))->toHaveCount(1)
        ->and($fake->callsTo('refund')[0]['paymentTransactionId'])->toBe('iyz_item_txn_refund')
        ->and($fake->callsTo('refund')[0]['amount'])->toBe(4200);

    $this->assertDatabaseHas('transactions', [
        'iyzico_id' => 'iyz_pay_refund',
        'iyzico_payment_transaction_id' => 'iyz_item_txn_refund',
        'status' => 'refunded',
    ]);
});
