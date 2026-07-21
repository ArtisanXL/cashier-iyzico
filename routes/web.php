<?php

use ArtisanXL\CashierIyzico\Http\Controllers\WebhookController;
use ArtisanXL\CashierIyzico\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::post('iyzico/webhook', WebhookController::class)
    ->middleware(VerifyWebhookSignature::class)
    ->name('cashier-iyzico.webhook');
