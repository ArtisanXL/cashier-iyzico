<?php

namespace ArtisanXL\CashierIyzico\Concerns;

use ArtisanXL\CashierIyzico\Address;
use ArtisanXL\CashierIyzico\Buyer;
use ArtisanXL\CashierIyzico\Cashier;
use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use ArtisanXL\CashierIyzico\Exceptions\IncompletePayment;
use ArtisanXL\CashierIyzico\Payment;
use ArtisanXL\CashierIyzico\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Iyzipay\Model\PaymentItem;

/**
 * @mixin Model
 */
trait PerformsCharges
{
    abstract public function gateway(): IyzicoGatewayContract;

    /**
     * @return MorphMany<Transaction, $this>
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'billable')->orderByDesc('created_at');
    }

    /**
     * Charge the given amount (in minor units / kuruş). When the caller requests
     * 3-D Secure (options['3ds'] or a callback_url), the charge is initialized
     * and a Payment requiring action (carrying the 3DS html) is returned.
     *
     * @param  array<string, mixed>  $options
     */
    public function charge(int $amount, Buyer $buyer, Address $address, array $options = []): Payment
    {
        $currency = strtoupper($options['currency'] ?? Cashier::currency());
        $paymentCard = $options['payment_card'] ?? [];

        if (($options['3ds'] ?? false) || isset($options['callback_url'])) {
            $initialize = $this->gateway()->initializeThreedsCharge(
                $amount,
                $currency,
                $buyer,
                $address,
                $paymentCard,
                $options['callback_url'] ?? '',
                $options,
            );

            $this->recordTransaction($initialize->getPaymentId(), 'pending', $amount, $currency);

            return Payment::fromThreedsInitialize($initialize);
        }

        $result = $this->gateway()->charge($amount, $currency, $buyer, $address, $paymentCard, $options);

        $this->recordTransaction(
            $result->getPaymentId(),
            (string) $result->getPaymentStatus(),
            $amount,
            $currency,
            $this->firstPaymentTransactionId($result->getPaymentItems()),
        );

        $payment = Payment::fromCharge($result);

        if (! $payment->successful()) {
            throw new IncompletePayment($payment);
        }

        return $payment;
    }

    /**
     * Complete a previously initialized 3-D Secure charge after the callback.
     */
    public function completeThreedsCharge(string $paymentId, ?string $conversationData = null): Payment
    {
        $result = $this->gateway()->completeThreedsCharge($paymentId, $conversationData);

        $transaction = $this->transactions()->where('iyzico_id', $paymentId)->first();

        $transaction?->update([
            'status' => (string) $result->getPaymentStatus(),
            'iyzico_payment_transaction_id' => $this->firstPaymentTransactionId($result->getPaymentItems()) ?? $transaction->iyzico_payment_transaction_id,
        ]);

        return Payment::fromCharge($result);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function refund(string $transactionIyzicoId, array $options = []): Transaction
    {
        /** @var Transaction $transaction */
        $transaction = $this->transactions()->where('iyzico_id', $transactionIyzicoId)->firstOrFail();

        // Iyzico refunds key off the basket-item paymentTransactionId, not the
        // top-level paymentId -- it's returned synchronously in the charge/3DS-
        // complete response and captured into iyzico_payment_transaction_id at
        // that time (see recordTransaction()/completeThreedsCharge()). Falling
        // back to iyzico_id only covers rows persisted before that capture existed.
        $this->gateway()->refund(
            $transaction->iyzico_payment_transaction_id ?? $transaction->iyzico_id,
            $transaction->amount,
            $transaction->currency,
            $options['ip'] ?? null,
        );

        $transaction->update(['status' => 'refunded']);

        return $transaction;
    }

    private function recordTransaction(mixed $iyzicoId, string $status, int $amount, string $currency, ?string $paymentTransactionId = null): Transaction
    {
        $transaction = new Transaction([
            'billable_id' => $this->getKey(),
            'billable_type' => $this->getMorphClass(),
            'iyzico_id' => (string) $iyzicoId,
            'iyzico_payment_transaction_id' => $paymentTransactionId,
            'status' => $status,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        $transaction->save();

        return $transaction;
    }

    /**
     * @param  array<int, PaymentItem>|null  $paymentItems
     */
    private function firstPaymentTransactionId(?array $paymentItems): ?string
    {
        $item = $paymentItems[0] ?? null;

        return $item?->getPaymentTransactionId() !== null ? (string) $item->getPaymentTransactionId() : null;
    }
}
