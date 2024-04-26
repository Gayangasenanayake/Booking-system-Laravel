<?php

namespace Modules\Location\Entities;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static create(mixed $validated)
 * @method static findOrFail($location_id)
 */
class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'name',
        'address_line_1',
        'address_line_2',
        'city',
        'post_code',
        'instructions',
        'notes',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'location_id', 'id');
    }
}
