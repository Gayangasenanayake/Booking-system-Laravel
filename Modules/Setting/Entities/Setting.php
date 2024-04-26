<?php

namespace Modules\Setting\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\Entities\Image;

/**
 * @method static create(array $except)
 * @method static findOrFail($setting_id)
 * @method static first()
 */
class Setting extends Model
{

    protected $fillable = [
        'business_name',
        'number',
        'email',
        'business_registration',
        'address',
        'logo'
    ];

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

}
