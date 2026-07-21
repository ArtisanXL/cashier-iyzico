<?php

namespace ArtisanXL\CashierIyzico\Http\Controllers;

use ArtisanXL\CashierIyzico\Events\SubscriptionCanceled;
use ArtisanXL\CashierIyzico\Events\SubscriptionCreated;
use ArtisanXL\CashierIyzico\Events\SubscriptionUpdated;
use ArtisanXL\CashierIyzico\Events\TransactionCompleted;
use ArtisanXL\CashierIyzico\Events\WebhookHandled;
use ArtisanXL\CashierIyzico\Events\WebhookReceived;
use ArtisanXL\CashierIyzico\Subscription;
use ArtisanXL\CashierIyzico\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Decodes Iyzico webhook/callback payloads and syncs local subscription and
 * transaction state -- the authoritative Phase 1 sync path (see plan Step 7's
 * payload->event->state mapping). Unrecognized payloads still fire the
 * WebhookReceived/WebhookHandled envelope events but dispatch no domain
 * event, so new Iyzico callback types never error the endpoint.
 */
class WebhookController
{
    public function __invoke(Request $request): Response
    {
        $payload = (array) $request->json()->all();

        event(new WebhookReceived($payload));

        $event = match (true) {
            $this->isSubscriptionEvent($payload) => $this->syncSubscription($payload),
            $this->isTransactionEvent($payload) => $this->syncTransaction($payload),
            default => null,
        };

        if ($event !== null) {
            event($event);
        }

        event(new WebhookHandled($payload));

        return response()->noContent();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isSubscriptionEvent(array $payload): bool
    {
        return str_starts_with((string) ($payload['iyziEventType'] ?? ''), 'SUBSCRIPTION_');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isTransactionEvent(array $payload): bool
    {
        return in_array($payload['iyziEventType'] ?? null, ['API_AUTH', 'CHECKOUT_FORM_AUTH', 'API_REFUND'], true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncSubscription(array $payload): SubscriptionCreated|SubscriptionCanceled|SubscriptionUpdated|null
    {
        $subscription = Subscription::query()
            ->where('iyzico_id', $payload['subscriptionReferenceCode'] ?? null)
            ->first();

        if ($subscription === null) {
            return null;
        }

        $status = strtoupper((string) ($payload['status'] ?? ''));

        return match (true) {
            $status === 'ACTIVE' => $this->markSubscriptionActive($subscription, $payload),
            in_array($status, ['CANCELED', 'EXPIRED'], true) => $this->markSubscriptionCanceled($subscription, $payload),
            $status === 'UPGRADED' => $this->markSubscriptionUpdated($subscription, $payload),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function markSubscriptionActive(Subscription $subscription, array $payload): SubscriptionCreated
    {
        $subscription->fill(['status' => 'active'])->save();

        return new SubscriptionCreated($subscription, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function markSubscriptionCanceled(Subscription $subscription, array $payload): SubscriptionCanceled
    {
        $endsAt = $this->parseEndDate($payload) ?? $subscription->freshTimestamp();

        $subscription->fill([
            'status' => 'canceled',
            'ends_at' => $endsAt,
        ])->save();

        return new SubscriptionCanceled($subscription, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function parseEndDate(array $payload): ?Carbon
    {
        if (! isset($payload['endDate'])) {
            return null;
        }

        try {
            return Carbon::parse($payload['endDate']);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function markSubscriptionUpdated(Subscription $subscription, array $payload): SubscriptionUpdated
    {
        $subscription->fill([
            'pricing_plan_id' => $payload['pricingPlanReferenceCode'] ?? $subscription->pricing_plan_id,
            'product_id' => $payload['productReferenceCode'] ?? $subscription->product_id,
            'quantity' => $payload['quantity'] ?? $subscription->quantity,
        ])->save();

        return new SubscriptionUpdated($subscription, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncTransaction(array $payload): ?TransactionCompleted
    {
        $transaction = Transaction::query()
            ->where('iyzico_id', $payload['paymentId'] ?? $payload['iyziPaymentId'] ?? null)
            ->first();

        if ($transaction === null) {
            return null;
        }

        $transaction->fill([
            'status' => strtoupper((string) ($payload['status'] ?? $transaction->status)),
            'amount' => isset($payload['price']) ? $this->toMinorUnits((string) $payload['price']) : $transaction->amount,
        ])->save();

        return new TransactionCompleted($transaction, $payload);
    }

    private function toMinorUnits(string $decimal): int
    {
        return (int) round(((float) $decimal) * 100);
    }
}
