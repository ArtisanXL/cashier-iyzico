<?php

namespace ArtisanXL\CashierIyzico\Exceptions;

use Illuminate\Database\Eloquent\Model;

class InvalidCustomer extends IyzicoException
{
    public static function notYetCreated(Model $billable): self
    {
        return new self(
            get_class($billable).' does not have an Iyzico customer id. Create one via createAsIyzicoCustomer() first.'
        );
    }
}
