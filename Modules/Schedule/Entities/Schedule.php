<?php

namespace Modules\Schedule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Entities\PriceTier;
use Modules\Activity\Entities\ScheduleGroup;
use Modules\Booking\Entities\BookingItem;
use Modules\Location\Entities\Location;
use Modules\Staff\Entities\StaffMember;

/**
 * @method static findOrFail($schedule_id)
 * @method static whereHas(string $string, \Closure $param)
 * @method static find($schedule_id)
 * @method static create($schedule_array)
 * @method static where(string $string, false $false)
 */
class Schedule extends Model
{

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'allocated_slots',
        'min_number_of_places',
        'max_number_of_places',
        'activity_id',
        'price_tier_id',
        'is_deleted',
        'is_published',
        'booked_slots',
        'schedule_group_id',
        'price',
        'location_id',
        'change_lead_time',
        'cancel_lead_time',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(StaffMember::class, 'staff_member_schedules');
    }

    public function priceTier(): BelongsTo
    {
        return $this->belongsTo(PriceTier::class,'price_tier_id','id');
    }

    public function schedule_group(): BelongsTo
    {
        return $this->belongsTo(ScheduleGroup::class,'schedule_group_id','id');
    }

    public function location(): HasOne
    {
        return $this->hasOne(Location::class, 'id', 'location_id');
    }
}
