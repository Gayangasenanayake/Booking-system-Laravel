<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductPricingInfo;
use Modules\Product\Http\Requests\ProductPricingInfoRequest;
use Spatie\QueryBuilder\QueryBuilder;

class ProductPricingInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param $product_id
     * @return DataResource|JsonResponse
     */
    public function index($product_id): JsonResponse|DataResource
    {
        try {
            $pricing_info = QueryBuilder::for(ProductPricingInfo::class)
                ->where('product_id', $product_id)
                ->firstOrFail();
            return new DataResource($pricing_info);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     * @param ProductPricingInfoRequest $request
     * @param $product_id
     * @return DataResource|JsonResponse
     */
    public function store(ProductPricingInfoRequest $request, $product_id): JsonResponse|DataResource
    {
        try {
            $product = Product::findOrFail($product_id);
            if (!$product) {
                return response()->json(['message' => 'product not found'], 404);
            } else {
                if ($product->productPricingInfo()->exists()){
                    return response()->json(['message' => 'Already created price info for this product!'], 422);
                }
                $product_pricing_info = $product->productPricingInfo()->create($request->all());
                return new DataResource($product_pricing_info);
//                return response()->json(['message' => 'Product stock created successfully'], 200);
            }

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param ProductPricingInfoRequest $request
     * @param $product_id
     * @param $pricing_info_id
     * @return DataResource|JsonResponse
     */
    public function update(ProductPricingInfoRequest $request, $product_id, $pricing_info_id): JsonResponse|DataResource
    {
        try {
            $pricing_info = ProductPricingInfo::where('id', $pricing_info_id)->firstOrFail();
            $pricing_info->update($request->all());
            return response()->json(['message' => 'Product pricing info updated successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $product_id
     * @param $pricing_info_id
     * @return JsonResponse
     */
    public function destroy($product_id, $pricing_info_id): JsonResponse
    {
        try {
            $pricing_info = ProductPricingInfo::where('id', $pricing_info_id)->firstOrFail();
            $pricing_info->delete();
            return response()->json(['message' => 'Product pricing info deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
