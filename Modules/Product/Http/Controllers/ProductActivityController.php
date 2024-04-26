<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Activity\Entities\Activity;
use Modules\Product\Entities\ActivityProduct;
use Modules\Product\Entities\Product;
use Spatie\QueryBuilder\QueryBuilder;

class ProductActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param $id
     * @return DataResource|JsonResponse
     */
    public function show($id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::findOrFail($id);
            $product = Product::findOrFail($id);
            if($activity){
                $data=QueryBuilder::for(ActivityProduct::class)
                    ->where('activity_id', $id)
                    ->get();
                return new DataResource($data);
            }
            else if($product){
                $data=QueryBuilder::for(ActivityProduct::class)
                    ->where('product_id', $id)
                    ->get();
                return new DataResource($data);
            }
            else{
                return response()->json(['message'=>'Data not found'],422);
            }
        }catch (Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

//    public function showProduct($product_id): JsonResponse|DataResource
//    {
//        try {
//            $product = Activity::findOrFail($product_id);
//            if(!$product){
//                return response()->json(['message'=>'Activity not found'],422);
//            }
//            else{
//                $data=QueryBuilder::for(ActivityProduct::class)
//                    ->where('$product_id', $product)
//                    ->get();
//                return new DataResource($data);
//            }
//        }catch (Exception $e) {
//            return response()->json(['error'=>$e->getMessage()],500);
//        }
//    }

    /**
     * Show the form for creating a new resource.
     * @param $product_id
     * @param Request $request
     * @return JsonResponse|DataResource
     */
    public function create($product_id, Request $request): JsonResponse|DataResource
    {
        try {
            $request->validate([
               'activities' => 'required'
            ]);

            $product=Product::findOrFail($product_id);
            if(!$product){
                return response()->json(['message'=>'Product not found'],422);
            }

            foreach ($request->activities as $activity_id){
                $activity=Activity::find($activity_id);
                if(!$activity){
                    return response()->json(['message'=>'Activities not found'],422);
                }
                $product->activities()->attach($activity_id);
            }
            return response()->json(['message'=>'Selected Activities were assigned!'],200);
        }catch (\Exception $e) {
            return response()->json(['error'=>'Something went wrong!'],500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param $product_id
     * @param Request $request
     * @return JsonResponse
     */
    public function update($product_id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'activities' => 'required'
            ]);

            $product=Product::find($product_id);
            if(!$product){
                return response()->json(['message'=>'Product not found'],422);
            }
            $product->activities()->detach();
            foreach ($request->activities as $activity_id){
                $activity=Activity::find($activity_id);
                if(!$activity){
                    return response()->json(['message'=>'Activities not found'],422);
                }
                $product->activities()->attach($activity_id);
            }

            return response()->json(['message'=>'Selected Activities were assigned!'],200);
        }catch (\Exception $e) {
            return response()->json(['error'=>'Something went wrong!'],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return void
     */
    public function destroy($id)
    {
        //
    }
}
