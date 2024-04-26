<?php

namespace Modules\Activity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTag extends Model
{
    protected $fillable = [
        'activity_id',
        'name',
    ];


    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }
}
