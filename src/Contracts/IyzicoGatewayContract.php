<?php

namespace ArtisanXL\CashierIyzico\Contracts;

use ArtisanXL\CashierIyzico\Address;
use ArtisanXL\CashierIyzico\Buyer;
use Iyzipay\Model\Payment;
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
 * Single seam between the package and the Iyzico SDK. Every SDK static call
 * (Model::create()/retrieve()/update()) is routed through an implementation of
 * this contract so tests can bind a fake, and so amount conversion (integer
 * minor units in the package, decimal price strings at the SDK) lives in one
 * place -- the default implementation.
 *
 * @phpstan-type PaymentCard array<string, mixed>
 */
interface IyzicoGatewayContract
{
    public function createCustomer(Buyer $buyer, Address $address): SubscriptionCustomer;

    public function updateCustomer(string $customerReferenceCode, Buyer $buyer, Address $address): SubscriptionCustomer;

    public function updateCard(string $customerReferenceCode, string $callbackUrl): SubscriptionCardUpdate;

    /**
     * @param  array<string, mixed>  $paymentCard
     */
    public function createSubscription(string $pricingPlanReferenceCode, Buyer $buyer, Address $address, array $paymentCard): SubscriptionCreate;

    public function cancelSubscription(string $subscriptionReferenceCode): SubscriptionCancel;

    public function activateSubscription(string $subscriptionReferenceCode): SubscriptionActivate;

    public function upgradeSubscription(string $subscriptionReferenceCode, string $newPricingPlanReferenceCode): SubscriptionUpgrade;

    /**
     * @param  array<string, mixed>  $paymentCard
     * @param  array<string, mixed>  $options
     */
    public function charge(int $amount, string $currency, Buyer $buyer, Address $address, array $paymentCard, array $options = []): Payment;

    /**
     * @param  array<string, mixed>  $paymentCard
     * @param  array<string, mixed>  $options
     */
    public function initializeThreedsCharge(int $amount, string $currency, Buyer $buyer, Address $address, array $paymentCard, string $callbackUrl, array $options = []): ThreedsInitialize;

    public function completeThreedsCharge(string $paymentId, ?string $conversationData = null): ThreedsPayment;

    public function refund(string $paymentTransactionId, int $amount, string $currency, ?string $ip = null): Refund;
}
