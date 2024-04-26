<?php

namespace Modules\Activity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\Entities\Image;

/**
 * @method static where(string $string, $activity_id)
 * @method static findOrFail($seo_id)
 */
class Seo extends Model
{

    protected $fillable = [
        'meta_title',
        'meta_description',
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
