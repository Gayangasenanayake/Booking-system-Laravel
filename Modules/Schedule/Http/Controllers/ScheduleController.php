<?php

namespace Modules\Schedule\Http\Controllers;

use App\Http\Resources\DataResource;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Modules\Booking\Entities\BookingItem;
use Modules\Booking\Entities\BookingParticipant;
use Modules\Schedule\Entities\Schedule;
use Modules\Schedule\Http\Requests\SaveScheduleRequest;
use Modules\Schedule\Http\Requests\ScheduleFilterRequest;
use Modules\Schedule\Http\Requests\ScheduleRequest;
use Spatie\QueryBuilder\QueryBuilder;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $schedules = QueryBuilder::for(Schedule::class)
            ->allowedFilters(['activity_id', 'location_id', 'date','staff.id'])
            ->where('is_deleted', false)
            ->with(['staff','activity' => function ($query){
                $query->where('is_deleted',false);
                $query->select('id','title','brief_description');
                $query->addSelect(['base_price' => function ($query) {
                    $query->from('activity_pricing_infos')
                          ->whereColumn('activity_id', 'activities.id')
                          ->select('base_price')
                          ->limit(1);
                }]);
                $query->with([
                    'images' => function ($query) {
                        $query->select('imageable_id')
                            ->selectRaw('COALESCE(
                MAX(CASE WHEN collection = "thumbnail_image" THEN  link END),
                MAX(CASE WHEN collection = "main_image" THEN link END)
            ) AS link')
                            ->groupBy('imageable_id');
                    }
                ]);
            },'priceTier' => function ($query){
                $query->select('id','advertised_price');
            }])
            ->paginate(10)
            ->onEachSide(1);

        $schedules->getCollection()->transform(function ($schedule) {
            $schedule['start_time'] = Carbon::parse($schedule['start_time'])->format('H:i');
            $schedule['end_time'] = Carbon::parse($schedule['end_time'])->format('H:i');

//            $dateString = $schedule['date']; // Assuming $schedule['date'] contains a date string in 'DD/MM/YYYY' format
//            dd($dateString);
//            $carbonDate = Carbon::createFromFormat('d/m/Y', $dateString);
//            $schedule['date'] = $carbonDate->format('d/m/y');
//            $dateString = '31/12/2023';
//            $carbonDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dateString);
            return $schedule;
        });

        return DataResource::collection($schedules);
    }

    /**
     * Store a newly created resource in storage.
     * @param ScheduleRequest $request
     * @return DataResource|JsonResponse
     */
    public function store(ScheduleRequest $request): JsonResponse|DataResource
    {
        try {
            $schedule = Schedule::create($request->all());
            return new DataResource($schedule);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    /**
     * Show the specified resource.
     * @param $schedule_id
     * @return DataResource|JsonResponse
     */
    public function show($schedule_id): JsonResponse|DataResource
    {
        try {
            $schedule = Schedule::with([
                'activity' => function ($query) {
                    $query->select('id', 'title');
                    $query->where('is_deleted', false);
                },
                'staff',
                'location',
            ])->find($schedule_id);

            if ($schedule) {
                $scheduleArray = $schedule->toArray();
                return new DataResource($scheduleArray);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Schedule not found'
                ], 404);
            }
        }catch (\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage()
            ],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $schedule_id
     * @return DataResource|JsonResponse
     */
    public function destroy($schedule_id): JsonResponse|DataResource
    {
        try {
            $schedule = Schedule::findOrFail($schedule_id);
            $booking_items = BookingItem::where('item_type','schedule')->where('item_id',$schedule_id)->first();
            if ($booking_items){
                return response()->json(['message' => 'Can not remove. Schedule was booked!'], 422);
            }
            $schedule->update(['is_deleted' => true]);
            return response()->json([
                'status'=>false,
                'message'=>'Schedule removed!'
            ],200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    /**
     * Update the specified resource in storage.
     * @param ScheduleRequest $request
     * @param $schedule_id
     * @return DataResource|JsonResponse
     */
    public function update(ScheduleRequest $request, $schedule_id): JsonResponse|DataResource
    {
        try {
            $schedule = Schedule::findOrFail($schedule_id);
            if ($schedule->booked_slots != null && $request->allocated_slots < $schedule->booked_slots){
                return response()->json(['message' => "Allocated slots count is less than the booking slots!"], 422);
            }
            $request->merge(['is_published' => true]);
            $schedule->update($request->all());
            return new DataResource($schedule);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    //save as a draft
    public function save(SaveScheduleRequest $request): JsonResponse|DataResource
    {
        try {
            $request->merge(['is_published' => false]);
            $schedule = Schedule::create($request->all());
            return new DataResource($schedule);
        }catch (\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage()
            ],500);
        }
    }

    //update draft
    public function draftUpdate(SaveScheduleRequest $request, $schedule_id): JsonResponse|DataResource
    {
        try {
            $schedule = Schedule::find($schedule_id);
            if ($schedule){
                $request->merge(['is_published' => false]);
                $schedule->update($request->all());
                return new DataResource($schedule);
            }else{
                return response()->json([
                    'status'=>false,
                    'message'=>'Schedule not found'
                ],404);
            }
        }catch (\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage()
            ],500);
        }
    }

    public function reschedule(Request $request, $schedule): JsonResponse|DataResource
    {
        try {
            $schedule = Schedule::find($schedule);
            if ($schedule) {
                $rules = [
                    'date' => 'required|date|after:today',
                    'start_time' => 'required|date_format:H:i',
                    'end_time' => 'required|date_format:H:i',
                ];
                $request->validate($rules);
                $schedule_array = $schedule->toArray();
                $schedule_array['date'] = $request->date;
                $schedule_array['start_time'] = $request->start_time;
                $schedule_array['end_time'] = $request->end_time;
                $new_schedule = Schedule::create($schedule_array);
                $schedule->update(['is_deleted' => true]);

                $booking_items = BookingItem::where('item_id',$schedule->id)
                    ->where('item_type','schedule')->where('is_deleted',false)->with('booking')->get();
                if ($booking_items->count() > 0){
                    foreach ($booking_items as $item){
                        //update booking item id to new id
                        $item->update(['item_id'=>$new_schedule->id]);
                        $customer_data = $item->booking->customer;
                        $booking = $item->booking;
                        //send mail
                        $message = "
                                hi $customer_data->first_name
                                    According to your booking Ref: $booking->reference we are reschedule a activity from
                                 $schedule->date to $request->date.
                            ";
                        Mail::raw($message, function ($mail) use ($customer_data){
                            $mail->subject('Reschedule alert');
                            $mail->to($customer_data->email);
                        });
                    }
                }
                return new DataResource($new_schedule);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Schedule not found!',
                ], 404);
            }
        }catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function scheduleFilter(ScheduleFilterRequest $request)
    {
        try {
            $date = $request->input('date');
            $activity_id = $request->input('activity_id');
            if (!$date){
                $schedules = QueryBuilder::for(Schedule::class)
                    ->allowedFilters(['activity_id', 'staff_member_id', 'start_time'])
                    ->where('is_deleted', false)
                    ->where('is_published', true)
                    ->where('activity_id',$activity_id)
                    ->with(['activity' => function ($query){
                        $query->where('is_deleted',false);
                        $query->select('id','title','brief_description');
                        $query->addSelect(['base_price' => function ($query) {
                            $query->from('activity_pricing_infos')
                                ->whereColumn('activity_id', 'activities.id')
                                ->select('base_price')
                                ->limit(1);
                        }]);
                        $query->with([
                            'images' => function ($query) {
                                $query->where('collection', 'thumbnail_image');
                            }
                        ]);
                    },'priceTier' => function ($query){
                        $query->select('id','advertised_price');
                    }])
                    ->paginate(10)
                    ->onEachSide(1);
                if ($schedules->isEmpty()) {
                    return response()->json(['message' => 'No matching data found.'], 404);
                }
                return DataResource::collection($schedules);
            }
            elseif (!$activity_id){
                $schedules = QueryBuilder::for(Schedule::class)
                    ->allowedFilters(['activity_id', 'staff_member_id', 'start_time'])
                    ->where('is_deleted', false)
                    ->where('is_published', true)
                    ->where('date',$date)
                    ->with(['activity' => function ($query){
                        $query->where('is_deleted',false);
                        $query->select('id','title','brief_description');
                        $query->addSelect(['base_price' => function ($query) {
                            $query->from('activity_pricing_infos')
                                ->whereColumn('activity_id', 'activities.id')
                                ->select('base_price')
                                ->limit(1);
                        }]);
                        $query->with([
                            'images' => function ($query) {
                                $query->where('collection', 'thumbnail_image');
                            }
                        ]);
                    },'priceTier' => function ($query){
                        $query->select('id','advertised_price');
                    }])
                    ->paginate(10)
                    ->onEachSide(1);
                if ($schedules->isEmpty()) {
                    return response()->json(['message' => 'No matching data found.'], 404);
                }
                return DataResource::collection($schedules);
            }
            else{
                $schedules = QueryBuilder::for(Schedule::class)
                    ->allowedFilters(['activity_id', 'staff_member_id', 'start_time'])
                    ->where('is_deleted', false)
                    ->where('is_published', true)
                    ->where('date',$date)
                    ->where('activity_id',$activity_id)
                    ->with(['activity' => function ($query){
                        $query->where('is_deleted',false);
                        $query->select('id','title','brief_description');
                        $query->addSelect(['base_price' => function ($query) {
                            $query->from('activity_pricing_infos')
                                ->whereColumn('activity_id', 'activities.id')
                                ->select('base_price')
                                ->limit(1);
                        }]);
                        $query->with([
                            'images' => function ($query) {
                                $query->where('collection', 'thumbnail_image');
                            }
                        ]);
                    },'priceTier' => function ($query){
                        $query->select('id','advertised_price');
                    }])
                    ->paginate(10)
                    ->onEachSide(1);
                if ($schedules->isEmpty()) {
                    return response()->json(['message' => 'No matching data found.'], 404);
                }
                return DataResource::collection($schedules);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getBookingDetails($schedule_id): AnonymousResourceCollection
    {
        $booking_items = QueryBuilder::for(BookingItem::class)
            ->where('item_type','=','schedule')
            ->where('item_id','=',$schedule_id)
            ->with(['booking'=>function($query){
                $query->with(['booking_participants'=>function($query){
                    $query->count();
                }]);
            }])
            ->paginate(10)
            ->onEachSide(1);

        $participant_count = QueryBuilder::for(BookingParticipant::class)
            ->whereHas('booking.bookingItems', function ($query) use ($schedule_id) {
                $query->where('item_type', '=', 'schedule');
                $query->where('item_id', '=', $schedule_id);
            })
            ->count();

        $total_amount = 0;
        foreach ($booking_items as $item) {

                $total_item = $item->total * $item->number_of_slots;
                $total_amount += $total_item;
                $item->total_amount = $total_amount;
                $item->participant_count = $participant_count;
        }
        return DataResource::collection($booking_items);
    }

}
