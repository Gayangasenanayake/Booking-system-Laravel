<?php

namespace Modules\Staff\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Activity\Entities\ScheduleGroup;
use Modules\Core\Entities\Image;
use Modules\Schedule\Entities\Schedule;

/**
 * @method static findOrFail($activity_id)
 * @method static create(array $all)
 * @method static where(string $string, mixed $member)
 */
class StaffMember extends Model
{

    protected $fillable = [
        'name',
        'title',
        'experience',
        'profile_data',
        'status',
        'email',
        'is_deleted',
    ];

    /**
     * The schedules that belong to the StaffMember
     * @return BelongsToMany
     */
    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'staff_member_schedules',
            'staff_member_id', 'schedule_id');
    }
    public function scheduleGroups(): BelongsToMany
    {
        return $this->belongsToMany(ScheduleGroup::class, 'staff_member_schedule_groups');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
