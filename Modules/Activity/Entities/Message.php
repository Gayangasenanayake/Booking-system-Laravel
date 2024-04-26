<?php

namespace Modules\Activity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\Entities\Image;

class Message extends Model
{
    protected $fillable = [
        'subject',
        'body',
        'attachment_url',
        'reply_email',
        'from',
        'to',
        'days',
        'after_or_before',
        'activity_id',
        'is_deleted',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
