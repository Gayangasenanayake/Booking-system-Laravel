<?php

namespace Modules\Staff\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Activity\Entities\ScheduleGroup;

class StaffMemberScheduleGroup extends Model
{

    protected $fillable = [
        'staff_member_id',
        'schedule_group_id',
    ];

    /**
     * Get the staff member that owns the StaffMemberScheduleGroup
     * @return BelongsTo
     */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    /**
     * Get the schedule that owns the StaffMemberScheduleGroup
     * @return BelongsTo
     */
    public function schedulegroup(): BelongsTo
    {
        return $this->belongsTo(ScheduleGroup::class);
    }

}
