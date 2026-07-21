<?php

use ArtisanXL\CashierIyzico\Events\SubscriptionCanceled;
use ArtisanXL\CashierIyzico\Events\SubscriptionCreated;
use ArtisanXL\CashierIyzico\Events\SubscriptionUpdated;
use ArtisanXL\CashierIyzico\Events\TransactionCompleted;
use ArtisanXL\CashierIyzico\Events\WebhookHandled;
use ArtisanXL\CashierIyzico\Events\WebhookReceived;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->artisan('migrate')->run();

    config()->set('cashier-iyzico.webhook_secret', 'whsec_test');
});

/**
 * @param  array<string, mixed>  $payload
 */
function iyzicoWebhookSignature(array $payload, string $secret): string
{
    $eventType = (string) ($payload['iyziEventType'] ?? '');
    $id = (string) ($payload['paymentId'] ?? $payload['iyziPaymentId'] ?? $payload['subscriptionReferenceCode'] ?? '');
    $token = (string) ($payload['token'] ?? '');
    $conversationId = (string) ($payload['paymentConversationId'] ?? '');
    $status = (string) ($payload['status'] ?? '');

    return hash_hmac('sha256', $secret.$eventType.$id.$token.$conversationId.$status, $secret);
}

it('rejects a webhook request with a missing or invalid signature', function () {
    $this->postJson(route('cashier-iyzico.webhook'), ['iyziEventType' => 'API_AUTH'])
        ->assertStatus(403);

    $this->postJson(
        route('cashier-iyzico.webhook'),
        ['iyziEventType' => 'API_AUTH'],
        ['X-Iyz-Signature-V3' => 'not-the-right-signature'],
    )->assertStatus(403);
});

it('accepts a correctly signed webhook and dispatches the envelope events', function () {
    Event::fake([WebhookReceived::class, WebhookHandled::class]);

    $payload = ['iyziEventType' => 'UNKNOWN_TYPE', 'status' => 'WHATEVER'];
    $signature = iyzicoWebhookSignature($payload, 'whsec_test');

    $this->postJson(route('cashier-iyzico.webhook'), $payload, ['X-Iyz-Signature-V3' => $signature])
        ->assertNoContent();

    Event::assertDispatched(WebhookReceived::class);
    Event::assertDispatched(WebhookHandled::class);
});

it('dispatches SubscriptionCanceled and updates the persisted subscription row', function () {
    bindFakeGateway();

    $user = createBillableUser();
    $subscription = $user->newSubscription('default', 'prod_9', 'plan_9')
        ->create(testBuyer(), testAddress(), testPaymentCard());

    Event::fake([SubscriptionCanceled::class]);

    $payload = [
        'iyziEventType' => 'SUBSCRIPTION_ORDER_CANCELED',
        'subscriptionReferenceCode' => $subscription->iyzico_id,
        'status' => 'CANCELED',
    ];
    $signature = iyzicoWebhookSignature($payload, 'whsec_test');

    $this->postJson(route('cashier-iyzico.webhook'), $payload, ['X-Iyz-Signature-V3' => $signature])
        ->assertNoContent();

    Event::assertDispatched(SubscriptionCanceled::class);

    $subscription->refresh();
    expect($subscription->status)->toBe('canceled')
        ->and($subscription->ends_at)->not->toBeNull();
});

it('dispatches SubscriptionCreated and activates the persisted subscription row', function () {
    bindFakeGateway();

    $user = createBillableUser();
    $subscription = $user->newSubscription('default', 'prod_9', 'plan_9')
        ->create(testBuyer(), testAddress(), testPaymentCard());

    $subscription->update(['status' => 'pending']);

    Event::fake([SubscriptionCreated::class]);

    $payload = [
        'iyziEventType' => 'SUBSCRIPTION_ORDER_ACTIVATED',
        'subscriptionReferenceCode' => $subscription->iyzico_id,
        'status' => 'ACTIVE',
    ];
    $signature = iyzicoWebhookSignature($payload, 'whsec_test');

    $this->postJson(route('cashier-iyzico.webhook'), $payload, ['X-Iyz-Signature-V3' => $signature])
        ->assertNoContent();

    Event::assertDispatched(SubscriptionCreated::class);

    expect($subscription->refresh()->status)->toBe('active');
});

it('dispatches TransactionCompleted and updates the persisted transaction row', function () {
    $user = createBillableUser();
    $transaction = $user->transactions()->create([
        'iyzico_id' => 'pay_123',
        'status' => 'pending',
        'amount' => 1000,
        'currency' => 'TRY',
    ]);

    Event::fake([TransactionCompleted::class]);

    $payload = [
        'iyziEventType' => 'API_AUTH',
        'paymentId' => 'pay_123',
        'status' => 'SUCCESS',
        'price' => '12.50',
    ];
    $signature = iyzicoWebhookSignature($payload, 'whsec_test');

    $this->postJson(route('cashier-iyzico.webhook'), $payload, ['X-Iyz-Signature-V3' => $signature])
        ->assertNoContent();

    Event::assertDispatched(TransactionCompleted::class);

    $transaction->refresh();
    expect($transaction->status)->toBe('SUCCESS')
        ->and($transaction->amount)->toBe(1250);
});

it('dispatches TransactionCompleted for a CO-Form callback keyed on iyziPaymentId', function () {
    $user = createBillableUser();
    $transaction = $user->transactions()->create([
        'iyzico_id' => 'pay_456',
        'status' => 'pending',
        'amount' => 500,
        'currency' => 'TRY',
    ]);

    Event::fake([TransactionCompleted::class]);

    $payload = [
        'iyziEventType' => 'CHECKOUT_FORM_AUTH',
        'iyziPaymentId' => 'pay_456',
        'token' => 'tok_abc',
        'status' => 'SUCCESS',
        'price' => '5.00',
    ];
    $signature = iyzicoWebhookSignature($payload, 'whsec_test');

    $this->postJson(route('cashier-iyzico.webhook'), $payload, ['X-Iyz-Signature-V3' => $signature])
        ->assertNoContent();

    Event::assertDispatched(TransactionCompleted::class);

    $transaction->refresh();
    expect($transaction->status)->toBe('SUCCESS')
        ->and($transaction->amount)->toBe(500);
});

it('dispatches SubscriptionUpdated and updates plan/product/quantity on an upgrade payload', function () {
    bindFakeGateway();

    $user = createBillableUser();
    $subscription = $user->newSubscription('default', 'prod_9', 'plan_9')
        ->create(testBuyer(), testAddress(), testPaymentCard());

    Event::fake([SubscriptionUpdated::class]);

    $payload = [
        'iyziEventType' => 'SUBSCRIPTION_ORDER_UPGRADED',
        'subscriptionReferenceCode' => $subscription->iyzico_id,
        'status' => 'UPGRADED',
        'pricingPlanReferenceCode' => 'plan_10',
        'productReferenceCode' => 'prod_10',
        'quantity' => 3,
    ];
    $signature = iyzicoWebhookSignature($payload, 'whsec_test');

    $this->postJson(route('cashier-iyzico.webhook'), $payload, ['X-Iyz-Signature-V3' => $signature])
        ->assertNoContent();

    Event::assertDispatched(SubscriptionUpdated::class);

    $subscription->refresh();
    expect($subscription->pricing_plan_id)->toBe('plan_10')
        ->and($subscription->product_id)->toBe('prod_10')
        ->and($subscription->quantity)->toBe(3);
});

it('does not dispatch a domain event for an unrecognized payload but still responds successfully', function () {
    Event::fake([SubscriptionCreated::class, SubscriptionCanceled::class, TransactionCompleted::class]);

    $payload = ['iyziEventType' => 'SOME_FUTURE_TYPE', 'status' => 'MYSTERY'];
    $signature = iyzicoWebhookSignature($payload, 'whsec_test');

    $this->postJson(route('cashier-iyzico.webhook'), $payload, ['X-Iyz-Signature-V3' => $signature])
        ->assertNoContent();

    Event::assertNotDispatched(SubscriptionCreated::class);
    Event::assertNotDispatched(SubscriptionCanceled::class);
    Event::assertNotDispatched(TransactionCompleted::class);
});
