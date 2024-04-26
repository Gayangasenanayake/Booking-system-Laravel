<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Resources\DataResource;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Entities\ConfirmMessage;
use Spatie\QueryBuilder\QueryBuilder;

class ConfirmMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return JsonResponse|DataResource
     */
    public function index($activity_id): JsonResponse|DataResource
    {
        try {
            $message = QueryBuilder::for(ConfirmMessage::class)
                ->where('activity_id',$activity_id)
                // ->where('is_deleted',false)
                ->get();
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
        return new DataResource($message);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('activity::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @param $activity
     * @return JsonResponse
     */
    public function store(Request $request, $activity): JsonResponse
    {
        try {
            $activity = Activity::find($activity);
            if ($activity) {
                if ($activity->confirmMessage()->exists()) {
                    $activity->confirmMessage()->update($request->validate([
                        'message'=> 'required|string'
                    ]));
                }
                else{
                    $activity->confirmMessage()->create($request->validate([
                        'message'=> 'required|string'
                    ]));
                }
                return response()->json([
                    'status' => true,
                    'message' => 'Confirm message saved!'
                ], 200);
            } else {
                return response()->json([
                    'status' => true,
                    'message' => 'Activity not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('activity::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('activity::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $activity_id
     * @param $message_id
     * @return JsonResponse
     */
    public function update(Request $request, $activity_id, $message_id): JsonResponse
    {
        try {
            $result = Activity::find($activity_id);
            if ($result) {
                $message_result = $result->confirmMessage()->find($message_id);
                if ($message_result) {
                    $message_result->update($request->validate([
                        'message'=> 'required|string'
                    ]));
                    return response()->json([
                        'status'=>true,
                        'message'=>'Confirmation message updated'
                    ],200);
                }else{
                    return response()->json([
                        'status'=>true,
                        'message'=>'Message not found!'
                    ],404);
                }
            }else{
                return response()->json([
                    'status' => true,
                    'message' => 'Activity not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
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
            $result = Activity::find($activity_id);
            if ($result) {
                $message_result = $result->confirmMessage()->find($message_id);
                if ($message_result) {
                    $message_result->update(['is_deleted'=>true]);
                    return response()->json([
                        'status'=>true,
                        'message'=>'Message removed!'
                    ],200);
                }else{
                    return response()->json([
                        'status'=>true,
                        'message'=>'Message not found!'
                    ],404);
                }
            }else{
                return response()->json([
                    'status' => true,
                    'message' => 'Activity not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
