<?php

namespace ArtisanXL\CashierIyzico\Commands;

use Illuminate\Console\Command;

class CashierIyzicoCommand extends Command
{
    public $signature = 'cashier-iyzico';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
