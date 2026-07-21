<?php

// config for ArtisanXL/CashierIyzico
return [

    /*
     * Outbound request auth signing (IyziAuthV2Generator).
     * Distinct from webhook_secret below -- signing outbound requests and
     * verifying inbound webhook payloads are different concerns even though
     * Iyzico's own docs sometimes conflate them.
     */
    'api_key' => env('CASHIER_IYZICO_API_KEY'),

    'api_secret' => env('CASHIER_IYZICO_API_SECRET'),

    'base_url' => env('CASHIER_IYZICO_BASE_URL', 'https://sandbox-api.iyzipay.com'),

    'currency' => env('CASHIER_IYZICO_CURRENCY', 'TRY'),

    /*
     * Used only to verify inbound webhook/callback signatures. Never reused
     * from api_secret.
     */
    'webhook_secret' => env('CASHIER_IYZICO_WEBHOOK_SECRET'),

    'model' => env('CASHIER_IYZICO_MODEL', 'App\\Models\\User'),

    /*
     * Optional model for persisting/caching Iyzico buyer/customer data.
     * Left null by default -- Buyer/Address value objects are passed
     * directly to charge()/newSubscription() instead.
     */
    'customer_model' => env('CASHIER_IYZICO_CUSTOMER_MODEL'),

];
