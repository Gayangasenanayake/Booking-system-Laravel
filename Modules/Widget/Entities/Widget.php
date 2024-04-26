<?php

namespace Modules\Widget\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Modules\Activity\Entities\Activity;

/**
 * @method static create(mixed $validated)
 * @method static where(string $string, $widget_id)
 */
class Widget extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'widget_type',
        'script',
        'is_active',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string)Str::uuid();
        });
    }


    /**
     * activities
     * @return BelongsToMany
     */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'widget_activities', 'widget_id', 'activity_id');
    }

}
