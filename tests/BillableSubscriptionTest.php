<?php

use ArtisanXL\CashierIyzico\Subscription;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate')->run();
});

it('persists a subscription row with the gateway type and status on create', function () {
    $fake = bindFakeGateway();
    $fake->subscriptionReferenceCode = 'iyz_sub_001';
    $fake->subscriptionStatus = 'ACTIVE';

    $user = createBillableUser();

    $subscription = $user->newSubscription('default', 'prod_9', 'plan_9')
        ->create(testBuyer(), testAddress(), testPaymentCard());

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->type)->toBe('default')
        ->and($subscription->status)->toBe('ACTIVE')
        ->and($subscription->iyzico_id)->toBe('iyz_sub_001')
        ->and($subscription->product_id)->toBe('prod_9')
        ->and($subscription->pricing_plan_id)->toBe('plan_9');

    $this->assertDatabaseHas('subscriptions', [
        'billable_id' => $user->getKey(),
        'billable_type' => $user->getMorphClass(),
        'type' => 'default',
        'status' => 'ACTIVE',
        'iyzico_id' => 'iyz_sub_001',
    ]);

    $this->assertDatabaseHas('subscription_items', [
        'subscription_id' => $subscription->getKey(),
        'product_id' => 'prod_9',
        'pricing_plan_id' => 'plan_9',
    ]);

    expect($fake->callsTo('createSubscription'))->toHaveCount(1)
        ->and($fake->callsTo('createSubscription')[0]['pricingPlanReferenceCode'])->toBe('plan_9');
});

it('exposes subscription lookup helpers keyed on the type column', function () {
    bindFakeGateway();

    $user = createBillableUser();

    $user->newSubscription('default', 'prod_9', 'plan_9')
        ->create(testBuyer(), testAddress(), testPaymentCard());

    expect($user->subscribed('default'))->toBeTrue()
        ->and($user->subscribed('team'))->toBeFalse()
        ->and($user->subscription('default'))->toBeInstanceOf(Subscription::class)
        ->and($user->subscription('default')->active())->toBeTrue();
});

it('cancels a subscription through the gateway and marks it ended', function () {
    $fake = bindFakeGateway();

    $user = createBillableUser();

    $subscription = $user->newSubscription('default', 'prod_9', 'plan_9')
        ->create(testBuyer(), testAddress(), testPaymentCard());

    $subscription->cancel();

    expect($subscription->canceled())->toBeTrue()
        ->and($subscription->status)->toBe('canceled')
        ->and($fake->callsTo('cancelSubscription'))->toHaveCount(1)
        ->and($fake->callsTo('cancelSubscription')[0]['subscriptionReferenceCode'])->toBe('sub_ref_123');
});
