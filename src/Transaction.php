<?php

namespace ArtisanXL\CashierIyzico;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $iyzico_id
 * @property string|null $iyzico_payment_transaction_id
 * @property string $status
 * @property int $amount
 * @property string $currency
 * @property-read Model $billable
 */
class Transaction extends Model
{
    protected $guarded = [];

    /**
     * @return MorphTo<Model, $this>
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }
}
