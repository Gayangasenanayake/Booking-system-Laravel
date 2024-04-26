<?php

namespace Modules\Activity\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Http\Requests\ActivityMainInfoRequest;
use App\Http\Resources\DataResource;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class ActivityController extends Controller
{
    /**
     * Display a listing of the activities.
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $activities = Activity::with('pricingInfo')
            ->with(['images' => function ($query) {
                $query->select('imageable_id')
                    ->selectRaw('COALESCE(
                MAX(CASE WHEN collection = "thumbnail_image" THEN  link END),
                MAX(CASE WHEN collection = "main_image" THEN link END)
            ) AS link')
                    ->groupBy('imageable_id');
            }])
            ->with(['schedules' => function ($query) {
                $query->where('is_deleted',false)
                    ->selectRaw('activity_id, CAST(SUM(allocated_slots) AS UNSIGNED) AS total_allocated_slots')
                    ->selectRaw('activity_id, CAST(SUM(booked_slots) AS UNSIGNED) AS total_booked_slots')
                    ->groupBy('activity_id');
            }])
            ->with(['scheduleGroups' => function ($query) {
                $query->selectRaw('activity_id, CAST(SUM(allocated_slots) AS UNSIGNED) AS total_allocated_slots')
                    ->selectRaw('activity_id, CAST(SUM(booked_slots) AS UNSIGNED) AS total_booked_slots')
                    ->groupBy('activity_id');
            }])
            ->paginate(10)
            ->onEachSide(1);

        return DataResource::collection($activities);
    }

    public function show($activity_id): DataResource
    {
        $activity = Activity::with('tags')
            ->with('images')
            ->findOrFail($activity_id);
        return new DataResource($activity);
    }

    /**
     * Update activity main info
     * @param ActivityMainInfoRequest $request
     * @param $activity_id
     * @return DataResource|JsonResponse
     */
    public function update(ActivityMainInfoRequest $request, $activity_id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            $request->merge(['is_selecting_staff' => $request->is_selecting_staff == 'true' || $request->is_selecting_staff == 1 ? 1 : 0]);
            $activity->update($request->except('tags', 'main_image', 'thumbnail_image'));
            $tags = $request->input('tags');
            if ($tags){
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
//                if (is_string($request->tags)) {
//                    dd("hii");
//                    $lastChar = substr($tags, -1);
//                    if ($lastChar === ',') {
//                        $tags = substr($tags, 0, -1);
//                    }
//                    $tagsArray = explode(',', $request->tags);
//                    $request->merge(['tags' => $tagsArray]);
//                }
                $activity->tags()->delete();
                if ($tags !== [null]){
                    foreach ($tags as $value) {
                        $activity->tags()->create([
                            'name' => $value,
                            'taggable_id' => $activity->id,
                            'taggable_type' => 'App/Activity'
                        ]);
                    }
                }
            }

            $current_Main_image = $activity->images()->where('collection', 'main_image')->first();
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
                uploadImage($request->main_image, '/activity/main_image', $file_name, $width, $height);
                $activity->images()->create([
                    'imageable_id' => $activity->id,
                    'imageable_type' => Activity::class,
                    'collection' => 'main_image',
                    'link' => 'activity/main_image/' . $file_name
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


            $current_image = $activity->images()->where('collection', 'thumbnail_image')->first();
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
                uploadImage($request->thumbnail_image, '/activity/thumbnail_image', $file_name, $width, $height);
                $activity->images()->create([
                    'imageable_id' => $activity->id,
                    'imageable_type' => Activity::class,
                    'collection' => 'thumbnail_image',
                    'link' => 'activity/thumbnail_image/' . $file_name
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

            return new DataResource($activity);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create activity
     * @param ActivityMainInfoRequest $request
     * @return DataResource|JsonResponse
     */
    public function store(ActivityMainInfoRequest $request): JsonResponse|DataResource
    {
        try {
            DB::beginTransaction();
            $request->merge(['is_selecting_staff' => $request->is_selecting_staff == 'true' ? 1 : 0]);
            $activity = Activity::create($request->except('tags', 'main_image', 'thumbnail_image'));
            $tags = $request->input('tags', []);
            if ($tags && $tags !== [null]) {
                if (!empty($tags) && end($tags) === null) {
                    array_pop($tags);
                }
                foreach ($tags as $value) {
                    $activity->tags()->create([
                        'name' => $value,
                        'taggable_id' => $activity->id,
                        'taggable_type' => 'App/Activity'
                    ]);
                }
            }

            if ($request->hasFile('main_image')) {
                $width = 600;
                $height = 400;
                $file = $request->file('main_image');
                $file_name = $file->getClientOriginalName();
                $file_name = $file_name . '_' . time() . '.' . $file->getClientOriginalExtension();
                uploadImage($request->main_image, '/activity/main_image', $file_name,$width,$height);
                $activity->images()->create([
                    'imageable_id' => $activity->id,
                    'imageable_type' => Activity::class,
                    'collection' => 'main_image',
                    'link' => 'activity/main_image/' . $file_name,
                ]);
            }

            if ($request->hasFile('thumbnail_image')) {
                $width = 120;
                $height = 120;
                $file = $request->file('thumbnail_image');
                $file_name = $file->getClientOriginalName();
                $file_name = $file_name . '_' . time() . '.' . $file->getClientOriginalExtension();
                uploadImage($request->thumbnail_image, '/activity/thumbnail_image', $file_name,$width,$height);
                $activity->images()->create([
                    'imageable_id' => $activity->id,
                    'imageable_type' => Activity::class,
                    'collection' => 'thumbnail_image',
                    'link' => 'activity/thumbnail_image/' . $file_name,
                ]);
            }

            $activity = Activity::with(['tags', 'images'])
                ->findOrFail($activity->id);

            $activity->pricingInfo()->create();
            $seoData = [
                // Other SEO data fields here...
                'meta_title' => $request->title, // Set 'meta_title' with the value from the request
            ];
            // Create a new SEO entry and set the 'meta_title' column
            $activity->seo()->create($seoData);
            $redirect_url = '/activity/' . $activity->id;

            DB::commit();
            return new DataResource(
                [
                    'activity' => $activity,
                    'redirect_url' => $redirect_url
                ]
            );
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Soft delete activity
     * @param $activity_id
     * @return JsonResponse
     */
    public function destroy($activity_id): JsonResponse
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if ($activity->courses()->exists()) {
                return response()->json(['message' => 'Activity already assigned to courses!'], 422);
            } else if ($activity->schedules()->exists()) {
                return response()->json(['message' => 'Activity already assigned to schedules!'], 422);
            } else if ($activity->scheduleGroups()->exists()) {
                return response()->json(['message' => 'Activity already assigned to schedule groups!'], 422);
            } else {
                $activity->update(['is_deleted' => true]);
                $activity->priceTiers()->update(['is_deleted' => true]);
                $activity->tags()->delete();
                $activity->pricingInfo()->delete();
                $activity->messages()->update(['is_deleted' => true]);
                $activity->seo()->update(['is_deleted' => true]);
                $activity->confirmMessage()->delete();
                //todo: remove images from cloud
                $activity->images()->delete();
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
        return response()->json(['message' => 'Activity deleted successfully'], 200);
    }

    public function activityInfo(): JsonResponse|DataResource
    {
        try {
            $activities = QueryBuilder::for(Activity::class)
                ->select('id', 'title')
                ->where('is_deleted', false)
                ->allowedFilters('title')
                ->get();
            return new DataResource($activities);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
}
