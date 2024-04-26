<?php

namespace Modules\Activity\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\Entities\Image;
use Modules\Core\Entities\Tag;
use Modules\Course\Entities\Course;
use Modules\Product\Entities\Product;
use Modules\Schedule\Entities\Schedule;
use Modules\Staff\Entities\StaffMember;

/**
 * @method static findOrFail($activity_id)
 * @method static find($activity_id)
 * @method static create(array $except)
 */
class Activity extends Model
{
    protected $fillable = [
        'title',
        'activity_code',
        'qty_label',
        'qty_label_plural',
        'is_selecting_staff',
        'brief_description',
        'long_description',
        'is_deleted',
    ];

    /**
     * @return HasMany
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'activity_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class, 'activity_id', 'id');
    }

    /**
     * @return MorphMany
     */
    public function tags(): MorphMany
    {
        return $this->morphMany(Tag::class, 'taggable');
    }

    /**
     * @return HasOne
     */
    public function pricingInfo(): HasOne
    {
        return $this->hasOne(ActivityPricingInfo::class, 'activity_id', 'id');
    }


    /**
     * @return HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'activity_id', 'id');
    }


    /**
     * @return HasMany
     */
    public function scheduleGroups(): HasMany //change: BelongsToMany to HasMany
    {
        return $this->hasMany(ScheduleGroup::class);
    }


    /**
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'activity_id', 'id');
    }

    /*
     * @return HasMany
     */
    public function prerequisites(): HasMany
    {
        return $this->hasMany(Prerequisites::class, 'activity_id', 'id');
    }

    public function seo(): HasOne
    {
        return $this->hasOne(Seo::class, 'activity_id', 'id');
    }

    public function confirmMessage(): HasOne
    {
        return $this->hasOne(ConfirmMessage::class, 'activity_id', 'id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function bookingSetting(): HasOne
    {
        return $this->hasOne(BookingSetting::class, 'activity_id', 'id');
    }
}
