<?php

namespace ArtisanXL\CashierIyzico;

use Iyzipay\Model\PaymentResource;
use Iyzipay\Model\ThreedsInitialize;

/**
 * In-flight payment result wrapper. Not an Eloquent model -- it wraps the
 * gateway's charge / 3-D Secure initiate response so callers can inspect the
 * outcome (success, failure, or an action-required 3DS redirect).
 */
final class Payment
{
    public function __construct(
        private readonly string $status,
        private readonly ?string $paymentId = null,
        private readonly ?string $threeDSHtmlContent = null,
        private readonly ?object $rawResponse = null,
    ) {}

    public static function fromCharge(PaymentResource $payment): self
    {
        return new self(
            status: (string) $payment->getPaymentStatus(),
            paymentId: $payment->getPaymentId() !== null ? (string) $payment->getPaymentId() : null,
            rawResponse: $payment,
        );
    }

    public static function fromThreedsInitialize(ThreedsInitialize $initialize): self
    {
        return new self(
            status: 'pending',
            paymentId: $initialize->getPaymentId() !== null ? (string) $initialize->getPaymentId() : null,
            threeDSHtmlContent: $initialize->getHtmlContent() !== null ? (string) $initialize->getHtmlContent() : null,
            rawResponse: $initialize,
        );
    }

    public function status(): string
    {
        return $this->status;
    }

    public function paymentId(): ?string
    {
        return $this->paymentId;
    }

    public function threeDSHtmlContent(): ?string
    {
        return $this->threeDSHtmlContent;
    }

    public function requiresAction(): bool
    {
        return $this->threeDSHtmlContent !== null;
    }

    public function successful(): bool
    {
        // Iyzico's paymentStatus is "SUCCESS"/"FAILURE" (business-level payment
        // outcome), distinct from the SDK's Status::SUCCESS ("success", the
        // top-level request-succeeded flag) -- compare case-insensitively.
        return strtoupper($this->status) === 'SUCCESS';
    }

    public function asIyzicoResponse(): ?object
    {
        return $this->rawResponse;
    }
}
