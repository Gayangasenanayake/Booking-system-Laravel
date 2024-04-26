<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @method static where(string $string, mixed $tag)
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [ 'name', 'taggable_id', 'taggable_type'];

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }

}
