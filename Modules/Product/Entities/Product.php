<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Activity\Entities\Activity;
use Modules\Booking\Entities\BookingItem;
use Modules\Core\Entities\Image;
use Modules\Core\Entities\Tag;

/**
 * @method static create(array $except)
 * @method static findOrFail($product_id)
 * @method static where(string $string, mixed $title)
 * @method static find($item_id)
 */
class Product extends Model
{
    protected $fillable = [
        'title',
        'sku',
        'brief_description',
        'long_description',
        'is_deleted',
    ];


    /**
     * pricing info
     * @return HasOne
     */
    public function productPricingInfo(): HasOne
    {
        return $this->hasOne(ProductPricingInfo::class, 'product_id', 'id');
    }

    /**
     *tags
     * @return MorphMany
     */
    public function tags(): MorphMany
    {
        return $this->morphMany(Tag::class, 'taggable');
    }
    /**
     * stocks
     * @return HasOne
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class, 'product_id', 'id');
    }

    public function BookingItems():HasMany
    {
        return $this->hasMany(BookingItem::class,'product_id','id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class);
    }
}
