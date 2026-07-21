<?php

namespace ArtisanXL\CashierIyzico\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ArtisanXL\CashierIyzico\CashierIyzico
 */
class CashierIyzico extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ArtisanXL\CashierIyzico\CashierIyzico::class;
    }
}
