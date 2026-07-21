<?php

namespace ArtisanXL\CashierIyzico;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $subscription_id
 * @property string $iyzico_id
 * @property string $product_id
 * @property string $pricing_plan_id
 * @property int $quantity
 */
class SubscriptionItem extends Model
{
    protected $guarded = [];

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
