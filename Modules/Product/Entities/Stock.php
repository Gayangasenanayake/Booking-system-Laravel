<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static findOrFail($stock_id)
 * @method static where(string $string, $stock_id)
 */
class Stock extends Model
{

    protected $fillable = [
        'available_stock',
        'is_manage_stocks_for_product',
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
