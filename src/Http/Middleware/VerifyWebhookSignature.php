<?php

namespace ArtisanXL\CashierIyzico\Http\Middleware;

use ArtisanXL\CashierIyzico\Cashier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the X-Iyz-Signature-V3 header per Iyzico's HMAC-SHA256 webhook
 * recipe (see vendor/iyzico/iyzipay-php/samples/webhook_Signature_Validation.php):
 * hash_hmac('sha256', secretKey.eventType.id.token.conversationId.status, secretKey).
 * The "id" field is paymentId for direct API payments, iyziPaymentId for
 * CO-Form/Pay-with-iyzico callbacks (the sample uses different field names
 * for the same position depending on flow).
 *
 * Iyzico only documents this for payment/checkout-form callbacks. Subscription
 * webhooks have no published signature format, so the same field-selection
 * recipe is extended here using subscriptionReferenceCode as the "id".
 */
class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = Cashier::webhookSecret();
        $signature = (string) $request->header('X-Iyz-Signature-V3');

        if (blank($secret) || blank($signature) || ! hash_equals($this->expectedSignature($request, $secret), $signature)) {
            abort(403, 'Invalid webhook signature.');
        }

        return $next($request);
    }

    private function expectedSignature(Request $request, string $secret): string
    {
        $payload = (array) $request->json()->all();

        $eventType = (string) ($payload['iyziEventType'] ?? '');
        $id = (string) ($payload['paymentId'] ?? $payload['iyziPaymentId'] ?? $payload['subscriptionReferenceCode'] ?? '');
        $token = (string) ($payload['token'] ?? '');
        $conversationId = (string) ($payload['paymentConversationId'] ?? '');
        $status = (string) ($payload['status'] ?? '');

        $data = $secret.$eventType.$id.$token.$conversationId.$status;

        return hash_hmac('sha256', $data, $secret);
    }
}
