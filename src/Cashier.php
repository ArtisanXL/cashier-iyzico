<?php

namespace ArtisanXL\CashierIyzico;

use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use Illuminate\Database\Eloquent\Model;

class Cashier
{
    public static function gateway(): IyzicoGatewayContract
    {
        return app(IyzicoGatewayContract::class);
    }

    public static function apiKey(): ?string
    {
        return config('cashier-iyzico.api_key');
    }

    public static function apiSecret(): ?string
    {
        return config('cashier-iyzico.api_secret');
    }

    public static function baseUrl(): string
    {
        return config('cashier-iyzico.base_url');
    }

    public static function currency(): string
    {
        return strtolower(config('cashier-iyzico.currency'));
    }

    public static function webhookSecret(): ?string
    {
        return config('cashier-iyzico.webhook_secret');
    }

    public static function billableModel(): string
    {
        return config('cashier-iyzico.model');
    }

    public static function customerModel(): ?string
    {
        return config('cashier-iyzico.customer_model');
    }

    public static function findBillable(?string $iyzicoId): ?Model
    {
        if ($iyzicoId === null) {
            return null;
        }

        $model = static::billableModel();

        return $model::where('iyzico_customer_id', $iyzicoId)->first();
    }

    public static function formatAmount(int $amount, ?string $currency = null): string
    {
        $currency = $currency ?? static::currency();

        return number_format($amount / 100, 2).' '.strtoupper($currency);
    }
}
