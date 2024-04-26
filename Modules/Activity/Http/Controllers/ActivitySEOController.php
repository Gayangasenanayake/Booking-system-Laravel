<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Entities\Seo;
use Modules\Activity\Http\Requests\ActivitySEORequest;

class ActivitySEOController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return JsonResponse|DataResource
     */
    public function index($activity_id): JsonResponse|DataResource
    {
        try {
            $activity_seo_data = Seo::where('activity_id', $activity_id)
                ->with('images')
                ->first();
            return new DataResource($activity_seo_data);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong!'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param ActivitySEORequest $request
     * @param $activity_id
     * @return DataResource|JsonResponse
     */
    public function store(ActivitySEORequest $request, $activity_id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if ($activity->seo()->exists()) {
                $seo_id = $activity->seo->id;
                $this->update($request, $activity_id, $seo_id);
            }
            else{
                $activity = $activity->seo()->create($request->except('attachment'));

            }
            if ($request->hasFile('attachment')) {
                $width = 600;
                $height = 400;
                $file = $request->file('attachment');
                $file_name = $file->getClientOriginalName();
                $file_name = $file_name . '_' . time() . '.' . $file->getClientOriginalExtension();
                uploadImage($request->attachment, '/activity/seo', $file_name,$width,$height);
                $activity->images()->create([
                    'imageable_id' => $activity->id,
                    'imageable_type' => seo::class,
                    'collection' => 'attachment',
                    'link' => 'activity/seo/'.$file_name,
                ]);
            }
            return new DataResource($activity);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param ActivitySEORequest $request
     * @param $activity_id
     * @param $seo_id
     * @return DataResource|JsonResponse
     */
    public function update(ActivitySEORequest $request, $activity_id, $seo_id): JsonResponse|DataResource
    {
        try {
            $activity_seo_data = Seo::findOrFail($seo_id);
            if (!$activity_seo_data){
                return response()->json(['message' => 'SEO data not found'], 404);
            }
            $activity_seo_data->update($request->except('attachment'));

            $current_image = $activity_seo_data->images()->where('collection', 'attachment')->first();
            if ($request->hasFile('attachment')) {
                $width = 600;
                $height = 400;
                $file = $request->file('attachment');
                $file_name = $file->getClientOriginalName();
                $file_name = $file_name . '_' . time() . '.' . $file->getClientOriginalExtension();
                if ($current_image) {
                    Storage::disk('s3')->delete($current_image->link);
                    $current_image->delete();
                }
                uploadImage($request->attachment, '/activity/seo', $file_name,$width,$height);
                $activity_seo_data->images()->create([
                    'imageable_id' => $activity_seo_data->id,
                    'imageable_type' => seo::class,
                    'collection' => 'attachment',
                    'link' => 'activity/seo/'.$file_name,
                ]);
            }else if(!$request->images && $current_image){
                Storage::disk('s3')->delete($current_image->link);
                $current_image->delete();
            }

            return new DataResource($activity_seo_data);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $activity_id
     * @param $seo_id
     * @return DataResource|JsonResponse
     */
    public function destroy($activity_id, $seo_id): JsonResponse|DataResource
    {
        try {
            $activity_seo_data = Seo::findOrFail($seo_id);
            if (!$activity_seo_data){
                return response()->json(['message' => 'SEO data not found'], 404);
            }
            $activity_seo_data->delete();
            return new DataResource($activity_seo_data);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
