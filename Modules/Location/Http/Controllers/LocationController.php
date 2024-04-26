<?php

namespace Modules\Location\Http\Controllers;

use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Location\Entities\Location;
use Modules\Location\Http\Requests\LocationRequest;
use Spatie\QueryBuilder\QueryBuilder;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $locations=QueryBuilder::for(Location::class)
            ->paginate(10)
            ->onEachSide(1);
        return DataResource::collection($locations);
    }

    /**
     * Show the form for creating a new resource.
     * @return DataResource
     */
    public function showEnabled(): DataResource
    {
        $locations=QueryBuilder::for(Location::class)
            ->where('is_enabled',1)
            ->get();
        return new DataResource($locations);
    }

    /**
     * Store a newly created resource in storage.
     * @param LocationRequest $request
     * @return DataResource|JsonResponse
     */
    public function store(LocationRequest $request): JsonResponse|DataResource
    {
        try {
            $location = Location::create($request->validated());
            return new DataResource($location);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param $location_id
     * @return DataResource|JsonResponse
     */
    public function show($location_id): JsonResponse|DataResource
    {
        try {
            $location = QueryBuilder::for(Location::class)
                ->where('id', $location_id)
                ->firstOrFail();
            return new DataResource($location);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     * @param LocationRequest $request
     * @param $location_id
     * @return DataResource|JsonResponse
     */
    public function update(LocationRequest $request, $location_id): JsonResponse|DataResource
    {
        try {
            $location = Location::findOrFail($location_id);
            $location->update($request->validated());
            return new DataResource($location);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $location_id
     * @return JsonResponse
     */
    public function destroy($location_id): JsonResponse
    {
        try {
            $location = Location::findOrFail($location_id);
            $is_enabled=QueryBuilder::for(Location::class)
                ->select('is_enabled')
                ->where('id',$location_id)
                ->first();
            if($is_enabled->is_enabled){
                $location->update(['is_enabled' => false]);
                return response()->json(['message' => 'Location disable successfully!']);
            }
            else{
                $location->update(['is_enabled' => true]);
                return response()->json(['message' => 'Location enable successfully!']);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
