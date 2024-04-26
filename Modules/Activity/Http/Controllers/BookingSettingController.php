<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Resources\DataResource;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Http\Requests\BookingSettingRequest;

class BookingSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return DataResource|JsonResponse
     */
    public function index($activity_id)
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if ($activity) {
                if ($activity->bookingSetting()->exists()){
                    $booking_setting = $activity->bookingSetting;
                    return new DataResource($booking_setting);
                }else{
                    return response()->json(['message' => 'Activity has no booking settings!'], 404);
                }
            }
        } catch (\Exception $e){
            return response()->json(['message' => $e->getMessage()], 500);
        }
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
     * @param BookingSettingRequest $request
     * @param $activity_id
     * @return DataResource|JsonResponse
     */
    public function store(BookingSettingRequest $request, $activity_id)
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if($request->calender_style==='week-normal-view'){
                return response()->json(['message' => 'Please select calendar type'], 422);
            }
            if ($activity){
                if ($activity->bookingSetting()->exists()){
                    $booking_setting = $activity->bookingSetting;
                    $booking_setting->update($request->validated());
                    return response()->json(['message' => 'Booking settings update successfully!'], 200);
                }else{
                    $booking_setting = $activity->bookingSetting()->create($request->validated());
                    return new DataResource($booking_setting);
                }
            }
        }catch (\Exception $e){
            return response()->json(['message' => $e->getMessage()], 500);
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
     * @param BookingSettingRequest $request
     * @param $activity_id
     * @param $booking_setting_id
     * @return DataResource|JsonResponse
     */
    public function update(BookingSettingRequest $request, $activity_id, $booking_setting_id)
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if ($activity){
                if ($booking_setting = $activity->bookingSetting($booking_setting_id)){
                    $booking_setting->update($request->validated());
                    return response()->json(['message' => 'Booking settings update successfully!'], 200);
                }else{
                    $booking_setting = $activity->bookingSetting()->create($request->validated());
                    return new DataResource($booking_setting);
                }
            }
        }catch (\Exception $e){
            return response()->json(['message' => $e->getMessage()], 500);
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
