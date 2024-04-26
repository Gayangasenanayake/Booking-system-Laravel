<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Booking\Entities\BookingItem;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Stock;
use Modules\Product\Http\Requests\ProductStockRequest;
use Spatie\QueryBuilder\QueryBuilder;

class ProductStockController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param $product_id
     * @return DataResource|JsonResponse
     */
    public function index($product_id): JsonResponse|DataResource
    {
        try {
            $stock = QueryBuilder::for(Stock::class)
                ->where('product_id', $product_id)
                ->firstOrFail();
            return new DataResource($stock);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     * @param ProductStockRequest $request
     * @param $product_id
     * @return DataResource|JsonResponse
     */
    public function store(ProductStockRequest $request, $product_id): JsonResponse|DataResource
    {
        try {
            $product = Product::findOrFail($product_id);
            if (!$product) {
                return response()->json(['message' => 'product not found'], 404);
            } else {
                if ($product->stock()->exists()){
                    return response()->json(['message' => 'Already created stock for this product!'], 422);
                }
                $product->stock()->create([
                    'available_stock' => $request->current_stock,
                    'is_manage_stocks_for_product' => $request->is_manage_stocks_for_product
                ]);
                return response()->json(['message' => 'Product stock created successfully'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     * @param ProductStockRequest $request
     * @param $product_id
     * @param $stock_id
     * @return DataResource|JsonResponse
     */
    public function update(ProductStockRequest $request, $product_id, $stock_id): JsonResponse|DataResource
    {
        try {
            $stock = Stock::findOrFail($stock_id);
            if (!$stock) {
                return response()->json(['message' => 'Product not found'], 404);
            } else {
                $available_stock = $request->validated('current_stock');
                $stock->update([
                    'available_stock' => $available_stock
                ]);
                $manage_stock = $request->validated('is_manage_stocks_for_product');
                if ($manage_stock) {
                    $stock->update([
                        'is_manage_stocks_for_product' => $manage_stock
                    ]);
                }
                return response()->json(['message' => 'Stock updated successfully'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $product_id
     * @param $stock_id
     * @return JsonResponse
     */
    public function destroy($product_id, $stock_id): JsonResponse
    {
        try {
            $stock = Stock::where('id', $stock_id)->firstOrFail();
            $booking_items = BookingItem::where('item_type','product')->where('item_id',$stock->product->id)->first();
            if ($booking_items){
                return response()->json(['message' => 'Can not remove. product has bookings!'], 422);
            }
            $stock->delete();
            return response()->json(['message' => 'Stock deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
