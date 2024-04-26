<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Entities\ActivityPricingInfo;
use Modules\Activity\Http\Requests\ActivityPricingInfoRequest;

class ActivityPricingInfoController extends Controller
{
    public function index ($activity_id): JsonResponse|DataResource
    {
        $activity = Activity::findOrFail($activity_id);
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        } else {
            if ($activity->pricingInfo()->exists()) {
                $price_info = $activity->pricingInfo()->first();
            }else{
                return response()->json(['message' => 'Activity has no price info!'], 422);
            }
        }

        return new DataResource($price_info);
    }
    /**
     * Store a newly created resource in storage.
     * @param ActivityPricingInfoRequest $request
     * @param $activity_id
     * @return JsonResponse
     */
    public function store(ActivityPricingInfoRequest $request, $activity_id): JsonResponse
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $pricingInfoExist = $activity->pricingInfo()->first();

                if ($pricingInfoExist) {
                    // Update the existing pricing info
                    $pricingInfoExist->update([
                        'base_price' => $request->base_price,
                        'advertised_price' => $request->advertised_price,
                    ]);
                }
                else{
                    $activity->pricingInfo()->create($request->validated());

                }
                return response()->json(['message' => 'Pricing info created successfully'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param ActivityPricingInfoRequest $request
     * @param $activity_id
     * @param $price_info_id
     * @return JsonResponse
     */
    public function update(ActivityPricingInfoRequest $request, $activity_id, $price_info_id): JsonResponse
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                ActivityPricingInfo::findOrFail($price_info_id);
                $activity->pricingInfo()->update($request->validated());
                return response()->json(['message' => 'Pricing info updated successfully'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $activity_id
     * @param $pricing_info_id
     * @return JsonResponse
     */
    public function destroy($activity_id,$pricing_info_id): JsonResponse
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $activity->pricingInfo()->delete($pricing_info_id);
                return response()->json(['message' => 'Pricing info deleted successfully'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }
}
