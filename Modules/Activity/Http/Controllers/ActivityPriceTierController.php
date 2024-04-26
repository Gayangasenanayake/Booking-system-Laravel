<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Entities\PriceTier;
use Modules\Activity\Http\Requests\ActivityPriceTierRequest;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityPriceTierController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return AnonymousResourceCollection
     */
    public function index($activity_id): AnonymousResourceCollection
    {
        $price_tiers = QueryBuilder::for(PriceTier::class)
            ->where('activity_id', $activity_id)
            ->where('is_deleted', false)
            ->paginate(10)
            ->onEachSide(1);
        return DataResource::collection($price_tiers);
    }

    public function showAll($activity_id): AnonymousResourceCollection
    {
        $price_tiers = QueryBuilder::for(PriceTier::class)
            ->where('activity_id', $activity_id)
            ->where('is_deleted', false)
            ->get();
        return DataResource::collection($price_tiers);
    }
    /**
     * Store a newly created resource in storage.
     * @param ActivityPriceTierRequest $request
     * @param $activity_id
     * @return DataResource|JsonResponse
     */
    public function store(ActivityPriceTierRequest $request, $activity_id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            }
            else {
                $price_tier = $activity->priceTiers()->create($request->validated());
                return new DataResource($price_tier);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param ActivityPriceTierRequest $request
     * @param $activity_id
     * @param $price_tier_id
     * @return DataResource | JsonResponse
     */
    public function update(ActivityPriceTierRequest $request, $activity_id, $price_tier_id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $price_tier = PriceTier::findOrFail($price_tier_id);
                if (!$price_tier) {
                    return response()->json(['message' => 'Price Tier not found'], 404);
                } else {
                    $price_tier->update($request->validated());
                    return new DataResource($price_tier);
                }
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $activity_id
     * @param $price_tier_id
     * @return JsonResponse
     */
    public function destroy($activity_id, $price_tier_id): JsonResponse
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $price_tier = PriceTier::findOrFail($price_tier_id);
                if ($price_tier->schedules()->where('is_deleted',0)->exists() || $price_tier->scheduleGroups()->where('is_deleted',0)->exists()){
                    return response()->json(['message' => 'Cannot delete record because price tier has active relations'], 422);
                }
                $price_tier->update(['is_deleted'=>true]);
                return response()->json(['message' => 'Price Tier deleted successfully'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function priceInfo($activityId): JsonResponse|DataResource
    {
        try {
            $prices = PriceTier::select('id','name')->where('activity_id',$activityId)->where('is_deleted',false)->get();
            return new DataResource($prices);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
}
