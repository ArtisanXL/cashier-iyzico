<?php

namespace ArtisanXL\CashierIyzico;

use ArtisanXL\CashierIyzico\Commands\CashierIyzicoCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CashierIyzicoServiceProvider extends PackageServiceProvider
{
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
            ->hasMigration('create_cashier_iyzico_table')
            ->hasCommand(CashierIyzicoCommand::class);
    }
}
