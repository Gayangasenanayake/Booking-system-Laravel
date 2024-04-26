<?php

namespace Modules\Booking\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Activity\Entities\ScheduleGroup;
use Modules\Product\Entities\Product;
use Modules\Schedule\Entities\Schedule;

/**
 * @method static where(string $string, string $string1)
 */
class BookingItem extends Model
{

    protected $fillable = [
        'item_type',
        'item_id',
        'booking_id',
        // 'current_payment',
        'total',
        'number_of_slots',
        'quantity',
        'is_deleted'
    ];


    /**
     * @return BelongsTo
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'item_id', 'id');
    }

//    public function scheduleGroups(): BelongsTo
//    {
//        return $this->belongsTo(ScheduleGroup::class, 'schedule_group_id', 'id');
//    }

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id', 'id');
    }
}
