<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'product_id',
    ];

    protected static function newFactory()
    {
        return \Modules\Product\Database\factories\ActivityProductFactory::new();
    }
}
