<?php

namespace ArtisanXL\CashierIyzico\Events;

class WebhookReceived
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly array $payload) {}
}
