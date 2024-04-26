<?php

namespace Modules\Booking\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Entities\Customer;

/**
 * @method static create(array $array)
 * @method static findOrFail($booking_id)
 * @method static find($booking_id)
 * @method static where(string $string, $reference)
 */
class Booking extends Model
{


    protected $fillable = [
        'reference',
        'customer_id',
        'date',
        'time',
        'participants',
        'paid',
        'sub_total',
        'tax',
        'total',
        'status',
        'is_refunded',
        'is_deleted',
    ];

//    protected static function boot()
//    {
//        parent::boot();
//        static::creating(function ($booking) {
//            $booking->reference = tenant('id') . '-' . uniqid();
//        });
//    }

    /**
     * @return HasMany
     */
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class, 'booking_id', 'id');
    }


    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function booking_participants(): HasMany
    {
        return $this->hasMany(BookingParticipant::class, 'booking_id', 'id');
    }
}
