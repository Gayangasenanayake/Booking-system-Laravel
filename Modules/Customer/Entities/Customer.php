<?php

namespace Modules\Customer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Modules\Booking\Entities\Booking;

/**
 * @method static create($all)
 */
class Customer extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'street',
        'city',
        'province',
        'mobile',
        'age',
        // 'dietary_req',
        'is_deleted'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customer_id', 'id');
    }

}
