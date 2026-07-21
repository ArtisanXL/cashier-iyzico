<?php

namespace ArtisanXL\CashierIyzico\Gateway;

use ArtisanXL\CashierIyzico\Address;
use ArtisanXL\CashierIyzico\Buyer;
use ArtisanXL\CashierIyzico\Cashier;
use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Customer;
use Iyzipay\Model\Payment;
use Iyzipay\Model\PaymentCard;
use Iyzipay\Model\Refund;
use Iyzipay\Model\Subscription\SubscriptionActivate;
use Iyzipay\Model\Subscription\SubscriptionCancel;
use Iyzipay\Model\Subscription\SubscriptionCardUpdate;
use Iyzipay\Model\Subscription\SubscriptionCreate;
use Iyzipay\Model\Subscription\SubscriptionCustomer;
use Iyzipay\Model\Subscription\SubscriptionUpgrade;
use Iyzipay\Model\ThreedsInitialize;
use Iyzipay\Model\ThreedsPayment;
use Iyzipay\Options;
use Iyzipay\Request\CreatePaymentRequest;
use Iyzipay\Request\CreateRefundRequest;
use Iyzipay\Request\CreateThreedsPaymentRequest;
use Iyzipay\Request\Subscription\SubscriptionActivateRequest;
use Iyzipay\Request\Subscription\SubscriptionCancelRequest;
use Iyzipay\Request\Subscription\SubscriptionCardUpdateRequest;
use Iyzipay\Request\Subscription\SubscriptionCreateCustomerRequest;
use Iyzipay\Request\Subscription\SubscriptionCreateRequest;
use Iyzipay\Request\Subscription\SubscriptionUpdateCustomerRequest;
use Iyzipay\Request\Subscription\SubscriptionUpgradeRequest;

/**
 * Default gateway wrapping the iyzico/iyzipay-php SDK's static Model::create()
 * calls. This is the only place that touches Iyzico decimal price strings --
 * everything else in the package works in integer minor units.
 */
class IyzicoGateway implements IyzicoGatewayContract
{
    public static function options(): Options
    {
        $options = new Options;
        $options->setApiKey(Cashier::apiKey());
        $options->setSecretKey(Cashier::apiSecret());
        $options->setBaseUrl(Cashier::baseUrl());

        return $options;
    }

    public function createCustomer(Buyer $buyer, Address $address): SubscriptionCustomer
    {
        $request = new SubscriptionCreateCustomerRequest;
        $request->setCustomer($this->buildCustomer($buyer, $address));

        return SubscriptionCustomer::create($request, static::options());
    }

    public function updateCustomer(string $customerReferenceCode, Buyer $buyer, Address $address): SubscriptionCustomer
    {
        $request = new SubscriptionUpdateCustomerRequest;
        $request->setCustomerReferenceCode($customerReferenceCode);
        $request->setCustomer($this->buildCustomer($buyer, $address));

        return SubscriptionCustomer::update($request, static::options());
    }

    public function updateCard(string $customerReferenceCode, string $callbackUrl): SubscriptionCardUpdate
    {
        $request = new SubscriptionCardUpdateRequest;
        $request->setCustomerReferenceCode($customerReferenceCode);
        $request->setCallbackUrl($callbackUrl);

        return SubscriptionCardUpdate::update($request, static::options());
    }

    public function createSubscription(string $pricingPlanReferenceCode, Buyer $buyer, Address $address, array $paymentCard): SubscriptionCreate
    {
        $request = new SubscriptionCreateRequest;
        $request->setPricingPlanReferenceCode($pricingPlanReferenceCode);
        $request->setPaymentCard($this->buildPaymentCard($paymentCard));
        $request->setCustomer($this->buildCustomer($buyer, $address));

        return SubscriptionCreate::create($request, static::options());
    }

    public function cancelSubscription(string $subscriptionReferenceCode): SubscriptionCancel
    {
        $request = new SubscriptionCancelRequest;
        $request->setSubscriptionReferenceCode($subscriptionReferenceCode);

        return SubscriptionCancel::cancel($request, static::options());
    }

    public function activateSubscription(string $subscriptionReferenceCode): SubscriptionActivate
    {
        $request = new SubscriptionActivateRequest;
        $request->setSubscriptionReferenceCode($subscriptionReferenceCode);

        return SubscriptionActivate::update($request, static::options());
    }

    public function upgradeSubscription(string $subscriptionReferenceCode, string $newPricingPlanReferenceCode): SubscriptionUpgrade
    {
        $request = new SubscriptionUpgradeRequest;
        $request->setSubscriptionReferenceCode($subscriptionReferenceCode);
        $request->setNewPricingPlanReferenceCode($newPricingPlanReferenceCode);

        return SubscriptionUpgrade::update($request, static::options());
    }

    public function charge(int $amount, string $currency, Buyer $buyer, Address $address, array $paymentCard, array $options = []): Payment
    {
        return Payment::create($this->buildPaymentRequest($amount, $currency, $buyer, $address, $paymentCard, $options), static::options());
    }

    public function initializeThreedsCharge(int $amount, string $currency, Buyer $buyer, Address $address, array $paymentCard, string $callbackUrl, array $options = []): ThreedsInitialize
    {
        $request = $this->buildPaymentRequest($amount, $currency, $buyer, $address, $paymentCard, $options);
        $request->setCallbackUrl($callbackUrl);

        return ThreedsInitialize::create($request, static::options());
    }

    public function completeThreedsCharge(string $paymentId, ?string $conversationData = null): ThreedsPayment
    {
        $request = new CreateThreedsPaymentRequest;
        $request->setPaymentId($paymentId);
        $request->setConversationData($conversationData);

        return ThreedsPayment::create($request, static::options());
    }

    public function refund(string $paymentTransactionId, int $amount, string $currency, ?string $ip = null): Refund
    {
        $request = new CreateRefundRequest;
        $request->setPaymentTransactionId($paymentTransactionId);
        $request->setPrice($this->formatPrice($amount));
        $request->setCurrency($currency);
        $request->setIp($ip);

        return Refund::create($request, static::options());
    }

    /**
     * @param  array<string, mixed>  $paymentCard
     * @param  array<string, mixed>  $options
     */
    private function buildPaymentRequest(int $amount, string $currency, Buyer $buyer, Address $address, array $paymentCard, array $options): CreatePaymentRequest
    {
        $price = $this->formatPrice($amount);

        $request = new CreatePaymentRequest;
        $request->setPrice($price);
        $request->setPaidPrice($price);
        $request->setCurrency($currency);
        $request->setBasketId($options['basket_id'] ?? 'B'.$buyer->identityNumber);
        $request->setPaymentCard($this->buildPaymentCard($paymentCard));
        $request->setBuyer($buyer->toIyzicoBuyer());
        $request->setShippingAddress($address->toIyzicoAddress());
        $request->setBillingAddress($address->toIyzicoAddress());
        $request->setBasketItems($this->buildBasketItems($price, $options));

        return $request;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, BasketItem>
     */
    private function buildBasketItems(string $price, array $options): array
    {
        $item = new BasketItem;
        $item->setId($options['basket_item_id'] ?? 'BI1');
        $item->setName($options['basket_item_name'] ?? 'Charge');
        $item->setCategory1($options['basket_item_category'] ?? 'General');
        $item->setItemType(BasketItemType::VIRTUAL);
        $item->setPrice($price);

        return [$item];
    }

    /**
     * @param  array<string, mixed>  $paymentCard
     */
    private function buildPaymentCard(array $paymentCard): PaymentCard
    {
        $card = new PaymentCard;
        $card->setCardHolderName($paymentCard['card_holder_name'] ?? null);
        $card->setCardNumber($paymentCard['card_number'] ?? null);
        $card->setExpireMonth($paymentCard['expire_month'] ?? null);
        $card->setExpireYear($paymentCard['expire_year'] ?? null);
        $card->setCvc($paymentCard['cvc'] ?? null);
        $card->setCardToken($paymentCard['card_token'] ?? null);
        $card->setCardUserKey($paymentCard['card_user_key'] ?? null);
        $card->setRegisterCard($paymentCard['register_card'] ?? null);

        return $card;
    }

    private function buildCustomer(Buyer $buyer, Address $address): Customer
    {
        $customer = new Customer;
        $customer->setName($buyer->name);
        $customer->setSurname($buyer->surname);
        $customer->setIdentityNumber($buyer->identityNumber);
        $customer->setEmail($buyer->email);
        $customer->setGsmNumber($buyer->gsmNumber);
        $customer->setBillingContactName($address->contactName);
        $customer->setBillingCity($address->city);
        $customer->setBillingCountry($address->country);
        $customer->setBillingAddress($address->address);
        $customer->setBillingZipCode($address->zipCode);
        $customer->setShippingContactName($address->contactName);
        $customer->setShippingCity($address->city);
        $customer->setShippingCountry($address->country);
        $customer->setShippingAddress($address->address);
        $customer->setShippingZipCode($address->zipCode);

        return $customer;
    }

    private function formatPrice(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
