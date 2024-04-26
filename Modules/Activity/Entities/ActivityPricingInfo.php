<?php

namespace Modules\Activity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static select(string $string)
 * @method static findOrFail($price_info_id)
 */
class ActivityPricingInfo extends Model
{
    protected $fillable = [
        'base_price',
        'activity_id',
        'advertised_price',
    ];


    /**
     * @return BelongsTo
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }
}
