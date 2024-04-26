<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Activity\Entities\ScheduleGroup;
use Modules\Staff\Entities\StaffMember;

/**
 * @method static create(array $array)
 * @method static findOrFail($schedule_id)
 * @method static where(string $string, string $string1)
 */
class ScheduleStaff extends Model
{
    use HasFactory;

    protected $table = 'schedule_staffs';

    protected $fillable = [
        'staff_id',
        'schedule_id',
        'is_deleted'
    ];

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class,'id','staff_id');
    }

    public function staffSchedule(): BelongsTo
    {
        return $this->belongsTo(ScheduleGroup::class,'id','schedule_id');
    }
}
