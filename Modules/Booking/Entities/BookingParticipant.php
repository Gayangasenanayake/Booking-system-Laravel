<?php

namespace Modules\Booking\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingParticipant extends Model
{

    protected $fillable = [
        'booking_id',
        'first_name',
        'last_name',
        'email',
        'age',
        'dietary_requirements',
        'weight',
        'height',
        'health_issues',
        'other',
        'details',
    ];

    /**
     *
     * @return BelongsTo
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

}
