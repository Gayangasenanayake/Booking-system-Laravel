<?php

namespace Modules\Activity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static findOrFail($prerequisites_id)
 */
class Prerequisites extends Model
{
    protected $table = 'prerequisities';

    protected $fillable = [
        'field_type',
        'title',
        'description',
        'activity_id',
        'is_deleted',
        'options',
        'required'
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }
}
