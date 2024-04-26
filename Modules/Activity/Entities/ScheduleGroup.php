<?php

namespace Modules\Activity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Booking\Entities\BookingItem;
use Modules\Schedule\Entities\Schedule;
use Modules\Staff\Entities\StaffMember;

/**
 * @method static findOrFail($schedule_group_id)
 */
class ScheduleGroup extends Model
{

    protected $table = 'schedule_groups';

    protected $fillable = [
        'from_date',
        'to_date',
        'day',
        'start_time',
        'end_time',
        'allocated_slots',
        'min_number_of_places',
        'max_number_of_places',
        'activity_id',
        'price_tier_id',
        'is_deleted',
        'booked_slots',
        'is_published',
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
        return $this->belongsToMany(StaffMember::class, 'staff_member_schedule_groups');
    }

    public function priceTier(): BelongsTo
    {
        return $this->belongsTo(PriceTier::class,'price_tier_id','id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class,'schedule_group_id','id');
    }
}
