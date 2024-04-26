<?php

namespace Modules\Activity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSetting extends Model
{
    protected $table = 'booking_settings';

    protected $fillable = [
        'is_available_to_book',
        'calender_style',
        'activity_id'
    ];

    public function Activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class,'activity_id','id');
    }
}
