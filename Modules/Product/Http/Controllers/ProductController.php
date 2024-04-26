<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Booking\Entities\BookingItem;
use Modules\Core\Entities\Tag;
use Modules\Product\Entities\Product;
use Modules\Product\Http\Requests\ProductRequest;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(): JsonResponse|AnonymousResourceCollection
    {
        try {
            $product = QueryBuilder::for(Product::class)
                ->with(['productPricingInfo', 'stock', 'tags'])
                ->with(['images' => function ($query) {
                    $query->select('imageable_id')
                        ->selectRaw('COALESCE(
                MAX(CASE WHEN collection = "thumbnail_image" THEN  link END),
                MAX(CASE WHEN collection = "main_image" THEN link END)
            ) AS link')
                        ->groupBy('imageable_id');
                }])
                ->paginate(10)
                ->onEachSide(1);
            return DataResource::collection($product);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function show($product_id): DataResource
    {
        $product = Product::with('tags', 'productPricingInfo', 'stock', 'images','activities:id,title')->findOrFail($product_id);
        return new DataResource($product);
    }

    /**
     * Store a newly created resource in storage.
     * @param ProductRequest $request
     * @return DataResource|JsonResponse
     */
    public function store(ProductRequest $request): JsonResponse|DataResource
    {
        try {
//            DB::transaction(function () use ($request) {
            $title = $request->input('title');
            $exists = Product::where('title', $title)->exists();
            if ($exists) {
                return response()->json(['message' => 'Already had that product title!'], 422);
            } else {
                $product = Product::create($request->except('tags', 'main_image', 'thumbnail_image'));
                $tags = $request->input('tags');
                if($tags && $tags !== [null]){
                    if (!empty($tags) && end($tags) === null) {
                        array_pop($tags);
                    }
                        foreach ($tags as $tag) {
                            $exists = Tag::where('name', $tag)->exists();
                            if (!$exists) {
                                $product->tags()->create([
                                    'name' => $tag,
                                    'taggable_id' => $product->id,
                                    'taggable_type' => 'App/product'
                                ]);
                            } else {
                                continue;
                            }
                        }
                }
                if ($request->hasFile('main_image')) {
                    $width = 600;
                    $height = 400;
                    $file = $request->file('main_image');
                    $file_name = $file->getClientOriginalName();
                    uploadImage($request->main_image, '/product/main_image', $file_name, $width, $height);
                    $product->images()->create([
                        'imageable_id' => $product->id,
                        'imageable_type' => Product::class,
                        'collection' => 'main_image',
                        'link' => 'product/main_image/' . $file_name,
                    ]);
                }

                if ($request->hasFile('thumbnail_image')) {
                    $width = 120;
                    $height = 120;
                    $file = $request->file('thumbnail_image');
                    $file_name = $file->getClientOriginalName();
                    uploadImage($request->thumbnail_image, '/product/thumbnail_image', $file_name, $width, $height);
                    $product->images()->create([
                        'imageable_id' => $product->id,
                        'imageable_type' => Product::class,
                        'collection' => 'thumbnail_image',
                        'link' => 'product/thumbnail_image/' . $file_name,
                    ]);
                }

                $product = Product::with(['tags', 'images'])
                    ->findOrFail($product->id);
                return response()->json(['message' => 'Product created successfully', 'id' => $product->id], 200);
            }
//            });
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     * @param ProductRequest $request
     * @param $product_id
     * @return JsonResponse
     */
    public function update(ProductRequest $request, $product_id): JsonResponse
    {
        try {
            $product = Product::findOrFail($product_id);
            $product->update($request->except('tags'));
            $tags = $request->input('tags', []);
            if($tags){
                if (is_array($tags)) {
                    if (!empty($tags) && end($tags) === null) {
                        array_pop($tags);
                    }
                }
                if (!is_array($tags)){
                    $lastChar = substr($tags, -1);
                    if ($lastChar === ',') {
                        $tags = substr($tags, 0, -1);
                    }
                    $tags = array_map('trim', explode(',', $tags));
                }
                if (is_string($request->tags)) {
                    $lastChar = substr($tags, -1);
                    if ($lastChar === ',') {
                        $tags = substr($tags, 0, -1);
                    }
                    $tagsArray = explode(',', $request->tags);
                    $request->merge(['tags' => $tagsArray]);
                }
                $product->tags()->delete();
                if ($tags !== [null]) {
                    foreach ($tags as $tag) {
                        $product->tags()->create([
                            'name' => $tag,
                            'taggable_id' => $product->id,
                            'taggable_type' => 'App/product'
                        ]);
                    }
                }
            }
            $current_Main_image = $product->images()->where('collection', 'main_image')->first();
            if ($request->hasFile('main_image')) {
                $width = 600;
                $height = 400;
                $file = $request->file('main_image');
                $file_name = $file->getClientOriginalName();
                $file_name = $file_name . '_' . time() . '.' . $file->getClientOriginalExtension();
                if ($current_Main_image) {
                    Storage::disk('s3')->delete($current_Main_image->link);
                    $current_Main_image->delete();
                }
                uploadImage($request->main_image, '/product/main_image', $file_name, $width, $height);
                $product->images()->create([
                    'imageable_id' => $product->id,
                    'imageable_type' => Product::class,
                    'collection' => 'main_image',
                    'link' => 'product/main_image/' . $file_name
                ]);
            }else if($request->images && $current_Main_image){
                $hasMainImage = false;
                foreach ($request->images as $image){
                    if ($image['collection'] == 'main_image'){
                        $hasMainImage = true;
                        break;
                    }
                }
                if (!$hasMainImage){
                    Storage::disk('s3')->delete($current_Main_image->link);
                    $current_Main_image->delete();
                }
            }else if(!$request->images && $current_Main_image){
                Storage::disk('s3')->delete($current_Main_image->link);
                $current_Main_image->delete();
            }

            $current_image = $product->images()->where('collection', 'thumbnail_image')->first();
            if ($request->hasFile('thumbnail_image')) {
                $width = 120;
                $height = 120;
                $file = $request->file('thumbnail_image');
                $file_name = $file->getClientOriginalName();
                $file_name = $file_name . '_' . time() . '.' . $file->getClientOriginalExtension();
                if ($current_image) {
                    Storage::disk('s3')->delete($current_image->link);
                    $current_image->delete();
                }
                uploadImage($request->thumbnail_image, '/product/thumbnail_image', $file_name, $width, $height);
                $product->images()->create([
                    'imageable_id' => $product->id,
                    'imageable_type' => Product::class,
                    'collection' => 'thumbnail_image',
                    'link' => 'product/thumbnail_image/' . $file_name
                ]);
            }else if($request->images && $current_image){
                $hasThumbnailImage = false;
                foreach ($request->images as $image){
                    if ($image['collection'] == 'thumbnail_image'){
                        $hasThumbnailImage = true;
                        break;
                    }
                }
                if (!$hasThumbnailImage){
                    Storage::disk('s3')->delete($current_image->link);
                    $current_image->delete();
                }
            }else if(!$request->images && $current_image){
                Storage::disk('s3')->delete($current_image->link);
                $current_image->delete();
            }

            return response()->json(['message' => 'Product updated successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $product_id
     * @return JsonResponse
     */
    public function destroy($product_id): JsonResponse
    {
        try {
            $product = Product::findOrFail($product_id);
            $booking_items = BookingItem::where('item_type', 'product')->where('item_id', $product_id)->first();
            if ($booking_items) {
                return response()->json(['message' => 'Can not remove. product has bookings!'], 422);
            }
            $product->update(['is_deleted' => true]);
            $product->productPricingInfo()->delete();
            $product->tags()->delete();
            $product->stock()->delete();
            return response()->json(['message' => 'Product deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
