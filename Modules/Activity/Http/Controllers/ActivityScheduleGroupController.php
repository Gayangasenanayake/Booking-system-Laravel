<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Resources\DataResource;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Entities\ScheduleGroup;
use Modules\Activity\Http\Requests\ActivityScheduleGroupRequest;
use Modules\Activity\Http\Requests\DraftScheduleGroupRequest;
use Modules\Activity\Http\Requests\RescheduleGroupRequest;
use Modules\Booking\Entities\BookingItem;
use Modules\Location\Entities\Location;
use Modules\Schedule\Entities\Schedule;
use Modules\Staff\Entities\StaffMember;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class ActivityScheduleGroupController extends Controller
{

    /**
     * Display a listing of the resource.
     * @param $activity_id
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index($activity_id): JsonResponse|AnonymousResourceCollection
    {
        $activity = Activity::findOrFail($activity_id);
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        } else {
            $schedule_groups = QueryBuilder::for(ScheduleGroup::class)
                ->with(['staff' => function ($query){
                    $query->select('staff_members.id','staff_members.name');
                },'priceTier' => function ($query){
                    $query->select('id','advertised_price');
                },'activity' => function ($query){
                    $query->select('id','title');
                    $query->addSelect(['base_price' => function ($query) {
                        $query->from('activity_pricing_infos')
                              ->whereColumn('activity_id', 'activities.id')
                              ->select('base_price')
                              ->limit(1);
                    }]);
                }])
                ->where('activity_id', $activity_id)
                ->where('is_deleted', false)
                ->paginate(10)
                ->onEachSide(1);

            $schedule_groups->getCollection()->transform(function ($schedule_group) {
                $schedule_group['start_time'] = Carbon::parse($schedule_group['start_time'])->format('H:i');
                $schedule_group['end_time'] = Carbon::parse($schedule_group['end_time'])->format('H:i');

                return $schedule_group;
            });

            return DataResource::collection($schedule_groups);
        }
    }

    public function getScheduleCount(ActivityScheduleGroupRequest $request,$activity_id): JsonResponse|AnonymousResourceCollection
    {
        $activity = Activity::findOrFail($activity_id);
        $activity_name = $activity->title;
        $days =$request->input('day');
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        } else {
            $location_id = $request->input('location_id');
            $start_time = $request->input('start_time');
            $end_time = $request->input('end_time');
            $from_date =$request->input('from_date');
            $to_date =$request->input('to_date');
            if($request->location_id){
                $location = Location::findOrFail($location_id);
                if (!$location) {
                    return response()->json(['message' => 'Location not found'], 404);
                }
                else if ($this->checkLocationAvailabilityInRange($location_id, $from_date,$to_date,$start_time, $end_time)){
                    return response()->json(['message' => 'This location is already occupied by another activity by your select time period!'], 422);
                }
            }
            if($request->assigned_staff) {
                $staff_members = $request->input('assigned_staff', []);
                foreach ($staff_members as $member) {
                    $staff = StaffMember::findOrFail($member);
                    $member_name = $staff->name;
                    if (!$staff) {
                        return response()->json(['message' => 'StaffMember not found'], 404);
                    } else if ($this->checkStaffAvailabilityInRange($member, $from_date, $to_date, $start_time, $end_time, $days)) {
                        return response()->json(['message' => "$member_name is already assigned for $activity_name"], 422);
                    }
                }
            }
            $fromDate = Carbon::parse($request->input('from_date'));
            $toDate = Carbon::parse($request->input('to_date'));
            $daysDifference = $toDate->diffInDays($fromDate);
            $selectedDays = $request->input('day');
            $scheduleCount = 0;
            for ($i = 0; $i <= $daysDifference; $i++) {
                $currentDate = $fromDate->copy()->addDays($i);
                $dayOfWeek = $currentDate->englishDayOfWeek;
                if (in_array($dayOfWeek, $selectedDays)) {
                    $scheduleCount++;
                }
            }
            return response()->json(['scheduleCount' => $scheduleCount]);
        }
    }


    /**
     * Store a newly created resource in storage.
     * @param ActivityScheduleGroupRequest $request
     * @param $activity_id
     * @return DataResource | JsonResponse
     */
    public function store(ActivityScheduleGroupRequest $request, $activity_id): JsonResponse|DataResource
    {
        try {
            DB::beginTransaction();
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                // $request->merge(['day' => serialize($request->day)]);
                $start_time = $request->input('start_time');
                $end_time = $request->input('end_time');
                $from_date =$request->input('from_date');
                $to_date =$request->input('to_date');
                $days =$request->input('day');
                $serializedDays = json_encode($request->day);
                $activity_name = $activity->title;
                if($request->location_id){
                    $location_id = $request->input('location_id');
                    $location = Location::findOrFail($location_id);
                    if (!$location) {
                        return response()->json(['message' => 'Location not found'], 404);
                    }
                    else if ($this->checkLocationAvailabilityInRange($location_id, $from_date,$to_date,$start_time, $end_time)){
                        return response()->json(['message' => 'This location is already occupied by another activity by your select time period!'], 422);
                    }
                }
                if($request->assigned_staff){
                    $staff_members = $request->input('assigned_staff', []);
                    foreach ($staff_members as $member){
                        $staff= StaffMember::findOrFail($member);
                        $member_name = $staff->name;
                        if (!$staff) {
                            return response()->json(['message' => 'StaffMember not found'], 404);
                        }
                        else if ($this->checkStaffAvailabilityInRange($member, $from_date,$to_date,$start_time, $end_time,$days)){
                            return response()->json(['message' => "$member_name is already assigned for $activity_name"], 422);
                        }
                    }
                    $schedule_group = $activity->scheduleGroups()->create(array_merge($request->except('assigned_staff'), ['day' => $serializedDays]));
                    $result = $this->createScheduleForDates($request, $activity, $start_time, $end_time, $schedule_group,$activity_id);
                    if ($result){
                        DB::rollBack();
                        return $result;
                    }
                    $schedule_group->staff()->attach($staff_members);
                }
                else{
                    $schedule_group = $activity->scheduleGroups()->create(array_merge($request->except('assigned_staff'), ['day' => $serializedDays]));
                    $result = $this->createScheduleForDates($request, $activity, $start_time, $end_time, $schedule_group,$activity_id);
                    if ($result){
                        DB::rollBack();
                        return $result;
                    }
                }
                DB::commit();
                return new DataResource($schedule_group);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $activity_id
     * @param $schedule_group_id
     * @return JsonResponse
     */
    public function destroy($activity_id, $schedule_group_id)
    {
        try {
            DB::beginTransaction();
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $schedule_group = ScheduleGroup::findOrFail($schedule_group_id);
                if (!$schedule_group) {
                    return response()->json(['message' => 'Schedule Group not found'], 404);
                } else {
                    $schedules = $schedule_group->schedules;
                    if ($schedules) {
                        foreach ($schedules as $schedule) {
                            $booking_items = BookingItem::where('item_type', 'schedule')->where('is_deleted', false)->where('item_id', $schedule->id)->first();
                            if ($booking_items) {
                                return response()->json(['message' => 'Can not remove or update. Schedule group has bookings!'], 422);
                            }
                            $schedule->staff()->detach();
                            $schedule->update(['is_deleted'=>true]);
                        }
                    }
                    //remove assign staff
                    $ids = $schedule_group->staff()->allRelatedIds();
                    if($ids){
                        $schedule_group->staff()->updateExistingPivot($ids, ['is_deleted' => true]);
                    }
                    $schedule_group->update(['is_deleted'=>true]);

                    DB::commit();
                    return response()->json(['message' => 'Schedule Group deleted successfully'], 200);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param ActivityScheduleGroupRequest $request
     * @param $activity_id
     * @param $schedule_group_id
     * @return JsonResponse | DataResource
     */
    public function update(ActivityScheduleGroupRequest $request, $activity_id, $schedule_group_id): JsonResponse|DataResource
    {
        try {
            DB::beginTransaction();
            $result = null;
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $schedule_group = ScheduleGroup::findOrFail($schedule_group_id);
                if (!$schedule_group) {
                    return response()->json(['message' => 'Schedule Group not found'], 404);
                } else {
                    if(!$request->input('price')){
                        $request->merge(['price'=>null]);
                    }
                    $booking_items = BookingItem::where('item_type', 'schedule_group')->where('item_id', $schedule_group->id)->where('is_deleted', false)->first();
                    if ($booking_items) {
                        return response()->json(['message' => 'Can not remove or update. Schedule group has bookings!'], 422);
                    }
                    if ($schedule_group->booked_slots != null && $request->allocated_slots < $schedule_group->booked_slots){
                        return response()->json(['message' => "Allocated slots count is less than the booking slots!"], 422);
                    }
                    $request->merge(['is_published'=>true]);
                    $serializedDays = json_encode($request->day);
                    $schedules = $schedule_group->schedules;
                    if ($schedules) {
                        foreach ($schedules as $schedule) {
                            $booking_items = BookingItem::where('item_type', 'schedule')->where('item_id', $schedule->id)->where('is_deleted', false)->first();
                            if ($booking_items) {
                                return response()->json(['message' => 'Can not remove or update. Schedules has bookings!'], 422);
                            }
                            $schedule->staff()->detach();
                            $schedule->update(['is_deleted' => true]);
                        }
                        $result = $this->createScheduleForDates($request, $activity, $request->start_time, $request->end_time, $schedule_group,$activity_id);
                        if ($result){
                            DB::rollBack();
                            return $result;
                        }
                    }else{
                        $result = $this->createScheduleForDates($request, $activity, $request->start_time, $request->end_time, $schedule_group,$activity_id);
                        if($result){
                            DB::rollBack();
                            return $result;
                        }
                    }
                    if($request->assigned_staff){
                        $staff_members = $request->input('assigned_staff', []);
                        $schedule_group->staff()->detach();
                        $schedule_group->staff()->attach($staff_members);
                    }
                    $schedule_group->update(array_merge($request->except('assigned_staff'), ['day' => $serializedDays]));
                    DB::commit();
                    return new DataResource($schedule_group);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function save(DraftScheduleGroupRequest $request, $activity_id): JsonResponse|DataResource
    {
        try {
            DB::beginTransaction();
            $activity = Activity::find($activity_id);
            if ($activity) {
                $request->merge(['is_published' => false]);
                $start_time = $request->input('start_time');
                $end_time = $request->input('end_time');
                $serializedDays = json_encode($request->day);
                $schedule_group = $activity->scheduleGroups()->create(array_merge($request->except('assigned_staff'), ['day' => $serializedDays]));
//                if($request->assigned_staff){
//                    $staff_members = $request->input('assigned_staff', []);
//                    $schedule_group->staff()->attach($staff_members);
//                }
//                if($request->day){
//                    $result = $this->createScheduleForDates($request, $activity, $start_time, $end_time, $schedule_group,$activity_id);
//                    if($result){
//                        DB::rollBack();
//                        return $result;
//                    }
//                }
                DB::commit();
                return new DataResource($schedule_group);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Activity not found!',
                ], 404);
            }
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updateDraft(DraftScheduleGroupRequest $request, $activity_id, $schedule_group): JsonResponse|DataResource
    {
        try {
            DB::beginTransaction();
            $result = null;
            $activity = Activity::find($activity_id);
            if ($activity){
                $schedule_group = ScheduleGroup::find($schedule_group);
                if ($schedule_group){
                    $booking_items = BookingItem::where('item_type', 'schedule_group')->where('item_id', $schedule_group->id)->where('is_deleted', false)->first();
                    if ($booking_items) {
                        return response()->json(['message' => 'Can not remove or update. Schedule group has bookings!'], 422);
                    }
                    // $request->merge(['day'=>serialize($request->day)]);
                    $request->merge(['is_published'=>false]);
                    if(!$request->input('price')){
                        $request->merge(['price'=>null]);
                    }
//                    if($request->day){
//                        $serializedDays = json_encode($request->day);
////                        $request->merge(['day' => $serializedDays]);
//                        $schedules = $schedule_group->schedules;
//                        if ($schedules) {
//                            foreach ($schedules as $schedule) {
//                                $booking_items = BookingItem::where('item_type', 'schedule')->where('item_id', $schedule->id)->where('is_deleted', false)->first();
//                                if ($booking_items) {
//                                    return response()->json(['message' => 'Can not remove or update. Schedule group has bookings!'], 422);
//                                }
//                                $schedule->staff()->detach();
//                                $schedule->update(['is_deleted' => true]);
//                            }
//                            $result = $this->createScheduleForDates($request, $activity, $request->start_time, $request->end_time, $schedule_group,$activity_id);
//                            if ($result){
//                                DB::rollBack();
//                                return $result;
//                            }
//                        }else{
//                            $result = $this->createScheduleForDates($request, $activity, $request->start_time, $request->end_time, $schedule_group,$activity_id);
//                            if ($result){
//                                DB::rollBack();
//                                return $result;
//                            }
//                        }
//                    }else{
//                        $schedules = $schedule_group->schedules;
//                        if ($schedules) {
//                            foreach ($schedules as $schedule) {
//                                $schedule->update($request->except(['day','assigned_staff']));
//                            }
//                        }
//                    }
                    $schedule_group->update($request->except('assigned_staff'));
                    if($request->assigned_staff){
                        $staff_members = $request->input('assigned_staff', []);
                        $schedule_group->staff()->detach();
                        $schedule_group->staff()->attach($staff_members);
                    }
                    DB::commit();
                    return new DataResource($schedule_group);
                }else{
                    return response()->json([
                        'status' => false,
                        'message' => 'Schedule group not found!',
                    ], 404);
                }
            }else{
                return response()->json([
                    'status' => false,
                    'message' => 'Activity not found!',
                ], 404);
            }
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function reschedule(RescheduleGroupRequest $request, $activity_id, $schedule_group_id)
    {
        try {
            $staff_members = null;
            $activity = Activity::find($activity_id);
            if ($activity){
                $schedule_group_data = ScheduleGroup::find($schedule_group_id);
                DB::beginTransaction();
                if ($schedule_group_data){
                    //assign new dates and times
                    $schedule_group_array = $schedule_group_data->toArray();
                    $schedule_group_array['from_date'] = $request->from_date;
                    $schedule_group_array['to_date'] = $request->to_date;
                    $schedule_group_array['start_time'] = $request->start_time;
                    $schedule_group_array['end_time'] = $request->end_time;

                    $request->merge($schedule_group_array);
                    $schedule_group_data->update(['is_deleted'=>true]);
                    $new_schedule_group = ScheduleGroup::create($schedule_group_array);

                    if($schedule_group_data->staff()->exists()){
                        $staff_members = $schedule_group_data->staff;
                        $request->merge(['assigned_staff'=>$staff_members]);
                        $schedule_group_data->staff()->detach();
                        $new_schedule_group->staff()->attach($staff_members);
                    }

                    $startDate = Carbon::createFromFormat('Y-m-d', $request->from_date);
                    $endDate = Carbon::createFromFormat('Y-m-d', $request->to_date);
                    $days = json_decode($schedule_group_data->day, true);
                    $daysOfWeek = array_map('strtolower', $days);
                    $dates = [];
                    $currentDate = $startDate->copy();
                    while ($currentDate->lte($endDate)) {
                        if (in_array(strtolower($currentDate->englishDayOfWeek), $daysOfWeek)) {
                            $dates[] = $currentDate->format('Y-m-d');
                        }
                        $currentDate->addDay();
                    }
                    // Store the events in the database using $dates array
                    foreach ($dates as $date) {
                        if($this->checkSchedulesExist($activity, $request->start_time, $request->end_time, $date)){
                            return response()->json(['message' => "On $date this time already has a schedule on this activity"],422);
                        }
                        //check staff busy or not
                        if ($schedule_group_data->staff()->exists()) {
                            foreach ($staff_members as $member){
                                $staff= StaffMember::findOrFail($member);
                                if (!$staff) {
                                    return response()->json(['message' => 'StaffMember not found'], 404);
                                }
                                if ($this->checkStaffAvailability($member, $date, $request->start_time, $request->end_time)){
                                    return response()->json(['message' => 'Staff members busy','staff_id' => $member], 404);
                                }
                            }
                            $request->merge(['date'=>$date]);
                            $schedule = $new_schedule_group->schedules()->create(array_merge($request->except('day'),['activity_id' => $activity->id]));
                            $schedule->staff()->attach($staff_members);
                        }else{
                            $request->merge(['date'=>$date]);
                            $new_schedule_group->schedules()->create(array_merge($request->except('day'),['activity_id' => $activity->id]));
                        }
                    }

                    $schedules = $schedule_group_data->schedules;
                    if ($schedules) {
                        foreach ($schedules as $schedule) {
                            $schedule->staff()->detach();
                            $schedule->update(['is_deleted' => true]);
                        }
                    }

                    $new_schedules = implode(", ", $new_schedule_group->schedules()->pluck('date')->toArray());

                    //check about bookings
                    $booking_items = BookingItem::where('item_id',$schedule_group_data->id)
                        ->where('item_type','schedule_group')->where('is_deleted',false)->with('booking')->get();
                    if ($booking_items->count() > 0){
                        foreach ($booking_items as $item){
                            //update booking item id to new id
                            $item->update(['item_id'=>$new_schedule_group->id]);
                            $customer_data = $item->booking->customer;
                            $booking = $item->booking;
                            //send mail
                            $message = "
                                hi $customer_data->first_name
                                    According to your booking Ref: $booking->reference we are reschedule a schedule group you booked. From
                                 $schedule_group_data->from_date to $new_schedule_group->from_date.
                                    End date: $new_schedule_group->to_date
                                    Start time: $new_schedule_group->start_time
                                    End time: $new_schedule_group->end_time
                                 So new schedules dates are here:
                                    $new_schedules
                            ";
                            Mail::raw($message, function ($mail) use ($customer_data){
                                $mail->subject('Reschedule alert');
                                $mail->to($customer_data->email);
                            });
                        }
                    }
                    DB::commit();
                    return new DataResource($new_schedule_group);
                }else{
                    return response()->json([
                        'status' => false,
                        'message' => 'Schedule group not found!',
                    ], 404);
                }
            }else{
                return response()->json([
                    'status' => false,
                    'message' => 'Activity not found!',
                ], 404);
            }
        } catch (Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    //support functions
    public function createScheduleForDates($request, $activity, $start_time, $end_time, $schedule_group,$activity_id)
    {
        $schedule_data = $request->validated();
        $startDate = Carbon::createFromFormat('Y-m-d', $request->from_date);
        $endDate = Carbon::createFromFormat('Y-m-d', $request->to_date);
        $created_at=Carbon::now();
        $daysOfWeek = array_map('strtolower', $request->input('day', []));
        $dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if (in_array(strtolower($currentDate->englishDayOfWeek), $daysOfWeek)) {
                $dates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }
        // Store the events in the database using $dates array
        foreach ($dates as $date) {
//            if($this->checkSchedulesExist($activity, $start_time, $end_time, $date)){
//                return response()->json(['message' => "On $date this time already has a schedule on this activity"],422);
//            }
            if($request->location_id){
                $location = $request->input('location_id');
                if ($this->checkLocationAvailability($location, $date, $start_time, $end_time,null)) {
                    return response()->json(['message' => 'This location is already occupied by another activity. Please select another location.'], 422);
                }
            }

            //check staff busy or not
            if ($request->assigned_staff) {
                $staff_members = $request->input('assigned_staff', []);
                foreach ($staff_members as $member){
                    $staff= StaffMember::findOrFail($member);
                    $member_name = $staff->name;
                    $activity_name = $activity->title;
                    if (!$staff) {
                        return response()->json(['message' => 'StaffMember not found'], 404);
                    }
                    if ($this->checkStaffAvailability($member, $date, $start_time, $end_time)){
                        return response()->json(['message' => "$member_name is already assigned for $activity_name"], 422);
                    }
                }
                    $schedule_data['date'] = $date;
                    $schedule_data['activity_id'] = $activity->id;
                    $schedule=$schedule_group->schedules()->create(array_merge($request->except('day'),$schedule_data));
                    $schedule->staff()->attach($staff_members);
            }else{
                    $schedule_data['date'] = $date;
                    $schedule_data['activity_id'] = $activity->id;
                    $schedule_group->schedules()->create(array_merge($request->except('day'),$schedule_data));
            }
        }
    }



    public function checkSchedulesExist($activity, $start_time, $end_time, $date)
    {
        return Schedule::where('is_deleted',false)->where('activity_id',$activity->id)->where('date',$date)->where(function ($query) use ($start_time, $end_time) {
            $query->where(function ($query) use ($start_time, $end_time) {
                $query->where('start_time', '>=', $start_time)
                    ->where('start_time', '<=', $end_time);
            })
                ->orWhere(function ($query) use ($start_time, $end_time) {
                    $query->where('end_time', '>=', $start_time)
                        ->where('end_time', '<=', $end_time);
                })
                ->orWhere(function ($query) use ($start_time, $end_time) {
                    $query->where('start_time', '=', $start_time)
                        ->where('end_time', '=', $end_time);
                });
        })
            ->exists();
    }

    public function checkStaffAvailability($member, $date, $start_time, $end_time,$schedule_id=null)
    {
        $query= Schedule::where('is_deleted',false)->where('date',$date)->where('is_published',true)->where(function ($query) use ($start_time, $end_time) {
            $query->where(function ($query) use ($start_time, $end_time) {
                $query->where('start_time', '>=', $start_time)
                    ->where('start_time', '<=', $end_time);
            })
                ->orWhere(function ($query) use ($start_time, $end_time) {
                    $query->where('end_time', '>=', $start_time)
                        ->where('end_time', '<=', $end_time);
                })
                ->orWhere(function ($query) use ($start_time, $end_time) {
                    $query->where('start_time', '=', $start_time)
                        ->where('end_time', '=', $end_time);
                });
        })
            ->whereHas('staff', function ($query) use ($member) {
                $query->where('staff_member_id', $member);
            });

        if(!is_null($schedule_id)){
            $query->where('id', '!=', $schedule_id);
        }
        return $query->exists();
    }

    public function checkLocationAvailability($location_id, $date, $start_time, $end_time, $schedule_id = null)
    {
        $query = Schedule::where('is_deleted', false)
            ->where('date', $date)
            ->where('is_published', true)
            ->where(function ($query) use ($start_time, $end_time) {
                $query->where(function ($query) use ($start_time, $end_time) {
                    $query->where('start_time', '>=', $start_time)
                        ->where('start_time', '<=', $end_time);
                })
                    ->orWhere(function ($query) use ($start_time, $end_time) {
                        $query->where('end_time', '>=', $start_time)
                            ->where('end_time', '<=', $end_time);
                    })
                    ->orWhere(function ($query) use ($start_time, $end_time) {
                        $query->where('start_time', '=', $start_time)
                            ->where('end_time', '=', $end_time);
                    });
            })
            ->where('location_id', $location_id);

        if (!is_null($schedule_id)) {
            $query->where('id', '!=', $schedule_id);
        }
        return $query->exists();
    }
    public function checkStaffAvailabilityInRange($member, $from_date, $to_date, $start_time, $end_time,$busy_days)
    {
        $query = Schedule::where('is_deleted', false)
            ->where('is_published', true)
            ->whereBetween('date', [$from_date, $to_date])
            ->where(function ($query) use ($start_time, $end_time) {
                $query->where(function ($query) use ($start_time, $end_time) {
                    $query->where('start_time', '>=', $start_time)
                        ->where('start_time', '<=', $end_time);
                })
                    ->orWhere(function ($query) use ($start_time, $end_time) {
                        $query->where('end_time', '>=', $start_time)
                            ->where('end_time', '<=', $end_time);
                    })
                    ->orWhere(function ($query) use ($start_time, $end_time) {
                        $query->where('start_time', '=', $start_time)
                            ->where('end_time', '=', $end_time);
                    });
            })
            ->whereHas('staff', function ($query) use ($member) {
                $query->where('staff_member_id', $member);
            })
            ->where(function ($query) use ($busy_days) {
                foreach ($busy_days as $day) {
                    $query->whereRaw("FIND_IN_SET(DAYNAME(date), ?) > 0", $day);
                }
            });
        return $query->exists();
    }

    public function checkLocationAvailabilityInRange($location_id, $from_date, $to_date, $start_time, $end_time)
    {
        $query = Schedule::where('is_deleted', false)
            ->whereBetween('date', [$from_date, $to_date])
            ->where('is_published', true)
            ->where(function ($query) use ($start_time, $end_time) {
                $query->where(function ($query) use ($start_time, $end_time) {
                    $query->where('start_time', '>=', $start_time)
                        ->where('start_time', '<=', $end_time);
                })
                    ->orWhere(function ($query) use ($start_time, $end_time) {
                        $query->where('end_time', '>=', $start_time)
                            ->where('end_time', '<=', $end_time);
                    })
                    ->orWhere(function ($query) use ($start_time, $end_time) {
                        $query->where('start_time', '=', $start_time)
                            ->where('end_time', '=', $end_time);
                    });
            })
            ->where('location_id', $location_id);
        return $query->exists();
    }
}
