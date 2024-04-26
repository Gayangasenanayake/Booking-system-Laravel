<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Entities\Prerequisites;
use Modules\Activity\Http\Requests\ActivityPrerequisitiesRequest;

class ActivityPrerequisitesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param $activity_id
     * @return DataResource
     */
    public function index($activity_id): DataResource
    {
        $prerequisites = DB::table('prerequisities')
            ->where('activity_id', $activity_id)
            ->where('is_deleted', false)
            ->paginate(10)
            ->onEachSide(1);

        return new DataResource($prerequisites);
    }

    public function view($activity_id): DataResource
    {
        $prerequisites = DB::table('prerequisities')
            ->where('activity_id', $activity_id)
            ->where('is_deleted', false)
            ->get();
        return new DataResource($prerequisites);
    }

    /**
     * Store a newly created resource in storage.
     * @param ActivityPrerequisitiesRequest $request
     * @param $activity_id
     * @return DataResource | JsonResponse
     */
    public function store(ActivityPrerequisitiesRequest $request, $activity_id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $prerequisites = $activity->prerequisites()->create($request->validated());
                return new DataResource($prerequisites);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $activity_id
     * @param $prerequisites_id
     * @return JsonResponse
     */
    public function destroy($activity_id, $prerequisites_id): JsonResponse
    {
        try {
            $prerequisites = Prerequisites::findOrFail($prerequisites_id);
            if (!$prerequisites) {
                return response()->json(['message' => 'Prerequisites not found'], 404);
            } else {
                $prerequisites->update(['is_deleted' => true]);
                return response()->json(['message' => 'Prerequisites deleted successfully'], 200);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param ActivityPrerequisitiesRequest $request
     * @param $activity_id
     * @param $prerequisites_id
     * @return JsonResponse | DataResource
     */
    public function update(ActivityPrerequisitiesRequest $request, $activity_id, $prerequisites_id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $prerequisites = $activity->prerequisites()->findOrFail($prerequisites_id);
                if (!$prerequisites) {
                    return response()->json(['message' => 'Prerequisites not found'], 404);
                } else {
                    $prerequisites->update($request->validated());
                    return new DataResource($prerequisites);
                }
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
