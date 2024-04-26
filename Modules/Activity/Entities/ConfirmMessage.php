<?php

namespace Modules\Activity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static where(string $string, $activity)
 */
class ConfirmMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'activity_id',
    ];
    public function Activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class,'activity_id','id');
    }
}
