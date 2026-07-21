<?php

namespace ArtisanXL\CashierIyzico\Tests\Fixtures;

use ArtisanXL\CashierIyzico\Address;
use ArtisanXL\CashierIyzico\Buyer;
use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use Iyzipay\Model\Payment;
use Iyzipay\Model\PaymentItem;
use Iyzipay\Model\Refund;
use Iyzipay\Model\Subscription\SubscriptionActivate;
use Iyzipay\Model\Subscription\SubscriptionCancel;
use Iyzipay\Model\Subscription\SubscriptionCardUpdate;
use Iyzipay\Model\Subscription\SubscriptionCreate;
use Iyzipay\Model\Subscription\SubscriptionCustomer;
use Iyzipay\Model\Subscription\SubscriptionUpgrade;
use Iyzipay\Model\ThreedsInitialize;
use Iyzipay\Model\ThreedsPayment;

/**
 * In-memory gateway used by tests. Records the calls it receives and returns
 * preset SDK response objects, so the package's persistence/mapping behavior can
 * be asserted without hitting the live Iyzico API.
 */
class FakeIyzicoGateway implements IyzicoGatewayContract
{
    /** @var array<int, array<string, mixed>> */
    public array $calls = [];

    public string $subscriptionReferenceCode = 'sub_ref_123';

    public string $subscriptionStatus = 'ACTIVE';

    public string $customerReferenceCode = 'cust_ref_123';

    public string $paymentId = 'pay_123';

    public string $paymentStatus = 'SUCCESS';

    public string $paymentTransactionId = 'pay_txn_123';

    public string $threeDSHtmlContent = '<html>3ds</html>';

    public function createCustomer(Buyer $buyer, Address $address): SubscriptionCustomer
    {
        $this->calls[] = ['method' => 'createCustomer', 'buyer' => $buyer, 'address' => $address];

        $customer = new SubscriptionCustomer;
        $customer->setReferenceCode($this->customerReferenceCode);

        return $customer;
    }

    public function updateCustomer(string $customerReferenceCode, Buyer $buyer, Address $address): SubscriptionCustomer
    {
        $this->calls[] = ['method' => 'updateCustomer', 'customerReferenceCode' => $customerReferenceCode];

        $customer = new SubscriptionCustomer;
        $customer->setReferenceCode($customerReferenceCode);

        return $customer;
    }

    public function updateCard(string $customerReferenceCode, string $callbackUrl): SubscriptionCardUpdate
    {
        $this->calls[] = ['method' => 'updateCard', 'customerReferenceCode' => $customerReferenceCode, 'callbackUrl' => $callbackUrl];

        $update = new SubscriptionCardUpdate;
        $update->setToken('card_update_token');

        return $update;
    }

    public function createSubscription(string $pricingPlanReferenceCode, Buyer $buyer, Address $address, array $paymentCard): SubscriptionCreate
    {
        $this->calls[] = [
            'method' => 'createSubscription',
            'pricingPlanReferenceCode' => $pricingPlanReferenceCode,
            'buyer' => $buyer,
            'address' => $address,
            'paymentCard' => $paymentCard,
        ];

        $subscription = new SubscriptionCreate;
        $subscription->setReferenceCode($this->subscriptionReferenceCode);
        $subscription->setPricingPlanReferenceCode($pricingPlanReferenceCode);
        $subscription->setSubscriptionStatus($this->subscriptionStatus);

        return $subscription;
    }

    public function cancelSubscription(string $subscriptionReferenceCode): SubscriptionCancel
    {
        $this->calls[] = ['method' => 'cancelSubscription', 'subscriptionReferenceCode' => $subscriptionReferenceCode];

        return new SubscriptionCancel;
    }

    public function activateSubscription(string $subscriptionReferenceCode): SubscriptionActivate
    {
        $this->calls[] = ['method' => 'activateSubscription', 'subscriptionReferenceCode' => $subscriptionReferenceCode];

        return new SubscriptionActivate;
    }

    public function upgradeSubscription(string $subscriptionReferenceCode, string $newPricingPlanReferenceCode): SubscriptionUpgrade
    {
        $this->calls[] = [
            'method' => 'upgradeSubscription',
            'subscriptionReferenceCode' => $subscriptionReferenceCode,
            'newPricingPlanReferenceCode' => $newPricingPlanReferenceCode,
        ];

        $upgrade = new SubscriptionUpgrade;
        $upgrade->setPricingPlanReferenceCode($newPricingPlanReferenceCode);
        $upgrade->setSubscriptionStatus($this->subscriptionStatus);

        return $upgrade;
    }

    public function charge(int $amount, string $currency, Buyer $buyer, Address $address, array $paymentCard, array $options = []): Payment
    {
        $this->calls[] = [
            'method' => 'charge',
            'amount' => $amount,
            'currency' => $currency,
            'buyer' => $buyer,
            'address' => $address,
            'paymentCard' => $paymentCard,
            'options' => $options,
        ];

        $item = new PaymentItem;
        $item->setPaymentTransactionId($this->paymentTransactionId);

        $payment = new Payment;
        $payment->setPaymentId($this->paymentId);
        $payment->setPaymentStatus($this->paymentStatus);
        $payment->setCurrency($currency);
        $payment->setPaymentItems([$item]);

        return $payment;
    }

    public function initializeThreedsCharge(int $amount, string $currency, Buyer $buyer, Address $address, array $paymentCard, string $callbackUrl, array $options = []): ThreedsInitialize
    {
        $this->calls[] = [
            'method' => 'initializeThreedsCharge',
            'amount' => $amount,
            'currency' => $currency,
            'callbackUrl' => $callbackUrl,
            'options' => $options,
        ];

        $initialize = new ThreedsInitialize;
        $initialize->setPaymentId($this->paymentId);
        $initialize->setHtmlContent($this->threeDSHtmlContent);

        return $initialize;
    }

    public function completeThreedsCharge(string $paymentId, ?string $conversationData = null): ThreedsPayment
    {
        $this->calls[] = ['method' => 'completeThreedsCharge', 'paymentId' => $paymentId];

        $payment = new ThreedsPayment;
        $payment->setPaymentId($paymentId);
        $payment->setPaymentStatus($this->paymentStatus);

        return $payment;
    }

    public function refund(string $paymentTransactionId, int $amount, string $currency, ?string $ip = null): Refund
    {
        $this->calls[] = [
            'method' => 'refund',
            'paymentTransactionId' => $paymentTransactionId,
            'amount' => $amount,
            'currency' => $currency,
        ];

        return new Refund;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function callsTo(string $method): array
    {
        return array_values(array_filter($this->calls, fn (array $call): bool => $call['method'] === $method));
    }
}
