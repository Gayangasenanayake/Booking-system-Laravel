<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static where(string $string, $pricing_info_id)
 */
class ProductPricingInfo extends Model
{

    protected $fillable = [
        'base_price',
        'advertised_price',
        'product_id'
    ];

    /**
     * product
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

}
