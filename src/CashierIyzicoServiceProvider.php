<?php

namespace ArtisanXL\CashierIyzico;

use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use ArtisanXL\CashierIyzico\Gateway\IyzicoGateway;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CashierIyzicoServiceProvider extends PackageServiceProvider
{
    public function packageRegistered(): void
    {
        $this->app->bind(IyzicoGatewayContract::class, IyzicoGateway::class);
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('cashier-iyzico')
            ->hasConfigFile()
            ->hasViews()
            ->runsMigrations()
            ->hasMigrations(
                'add_iyzico_id_to_users_table',
                'create_subscriptions_table',
                'create_subscription_items_table',
                'create_transactions_table',
            );
    }
}
