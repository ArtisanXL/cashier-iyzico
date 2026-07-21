<?php

namespace ArtisanXL\CashierIyzico\Tests\Fixtures;

use ArtisanXL\CashierIyzico\Billable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Billable;

    protected $table = 'users';

    protected $guarded = [];
}
