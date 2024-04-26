<?php

namespace Modules\Course\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Activity\Entities\Activity;

/**
 * @method static create(mixed $validated)
 * @method static findOrFail($course_id)
 * @method static find($item_id)
 */
class Course extends Model
{

    protected $fillable = [
        'name',
        'frequency',
        'price',
        'original_price',
        'summery',
        'long_description',
        'activity_id',
        'start_date',
        'end_date',
        'is_deleted',
        'sessions'
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }
}
