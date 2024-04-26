<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Resources\DataResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Entities\Message;
use Modules\Activity\Http\Requests\ActivityMessagesRequest;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param $activity_id
     * @return AnonymousResourceCollection
     */
    public function index($activity_id): AnonymousResourceCollection
    {
        $messages = QueryBuilder::for(Message::class)
            ->where('activity_id', $activity_id)
            ->where('is_deleted', false)
            ->with('images')
            ->paginate(10)
            ->onEachSide(1);

        return DataResource::collection($messages);
    }

    /**
     * Store a newly created resource in storage.
     * @param ActivityMessagesRequest $request
     * @param $activity_id
     * @return JsonResponse | DataResource
     */
    public function store(ActivityMessagesRequest $request, $activity_id): JsonResponse|DataResource
    {
        try {
            DB::beginTransaction();
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $message = $activity->messages()->create($request->except('attachment'));
                if ($request->hasFile('attachment')) {
                    $width = 600;
                    $height = 400;
                    $file = $request->file('attachment');
                    $file_name = $file->getClientOriginalName();
                    $file_name = $file_name . '_' . time() . '.' . $file->getClientOriginalExtension();
                    uploadImage($request->attachment, '/message/attachment', $file_name,$width,$height);
                    $message->images()->create([
                        'imageable_id' => $message->id,
                        'imageable_type' => Message::class,
                        'collection' => 'attachment',
                        'link' => 'message/attachment/'.$file_name,
                    ]);
                }
                DB::commit();
                return new DataResource($message);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param ActivityMessagesRequest $request
     * @param $activity_id
     * @param $message_id
     * @return DataResource | JsonResponse
     */
    public function update(ActivityMessagesRequest $request, $activity_id, $message_id): JsonResponse|DataResource
    {
        try {
            DB::beginTransaction();
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $message = $activity->messages()->findOrFail($message_id);
                if (!$message) {
                    return response()->json(['message' => 'Message not found'], 404);
                } else {
                    $message->update($request->except('attachment'));

                    $current_image = $message->images()->where('collection', 'attachment')->first();
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
                        uploadImage($request->attachment, '/message/attachment', $file_name,$width,$height);
                        $message->images()->create([
                            'imageable_id' => $message->id,
                            'imageable_type' => Message::class,
                            'collection' => 'attachment',
                            'link' => 'message/attachment/'.$file_name,
                        ]);
                    }else if(!$request->images && $current_image){
                        Storage::disk('s3')->delete($current_image->link);
                        $current_image->delete();
                    }

                    DB::commit();
                    return new DataResource($message);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $activity_id
     * @param $message_id
     * @return JsonResponse
     */
    public function destroy($activity_id, $message_id): JsonResponse
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $message = $activity->messages()->findOrFail($message_id);
                if (!$message) {
                    return response()->json(['message' => 'Message not found'], 404);
                } else {
                    $message->update(['is_deleted' => true]);
                        $message->images()->delete();
                    return response()->json(['message' => 'Message deleted successfully'], 200);
                }
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
