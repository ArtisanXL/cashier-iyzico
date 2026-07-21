<?php

namespace ArtisanXL\CashierIyzico;

use ArtisanXL\CashierIyzico\Concerns\InteractsWithIyzico;
use ArtisanXL\CashierIyzico\Concerns\ManagesCustomer;
use ArtisanXL\CashierIyzico\Concerns\ManagesPaymentMethods;
use ArtisanXL\CashierIyzico\Concerns\ManagesSubscriptions;
use ArtisanXL\CashierIyzico\Concerns\PerformsCharges;

trait Billable
{
    use InteractsWithIyzico;
    use ManagesCustomer;
    use ManagesPaymentMethods;
    use ManagesSubscriptions;
    use PerformsCharges;
}
