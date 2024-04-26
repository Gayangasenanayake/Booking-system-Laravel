<?php

namespace Modules\Activity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Schedule\Entities\Schedule;

/**
 * @method static where(string $string, mixed $name)
 * @method static findOrFail($price_tier_id)
 * @method static select(string $string)
 */
class PriceTier extends Model
{

    protected $fillable = [
        'activity_id',
        'name',
        'price',
        'advertised_price',
        'is_deleted',
        'effective_from_date',
        'effective_to_date',
        'minimum_number_of_participants',
        'maximum_number_of_participants',
    ];


    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'price_tier_id', 'id');
    }

    public function scheduleGroups(): HasMany
    {
        return $this->hasMany(ScheduleGroup::class,'price_tier_id','id');
    }
}
