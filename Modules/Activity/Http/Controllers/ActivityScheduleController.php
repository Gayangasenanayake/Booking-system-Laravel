<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Resources\DataResource;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Http\Requests\ActivityScheduleRequest;
use Modules\Activity\Http\Requests\DraftScheduleRequest;
use Modules\Activity\Http\Requests\RescheduleRequest;
use Modules\Booking\Entities\BookingItem;
use Modules\Schedule\Entities\Schedule;
use Modules\Staff\Entities\StaffMember;
use Spatie\QueryBuilder\QueryBuilder;
use function PHPUnit\Framework\isEmpty;

class ActivityScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param $activity_id
     * @return AnonymousResourceCollection
     */
    public function index($activity_id): AnonymousResourceCollection
    {
        $schedules = QueryBuilder::for(Schedule::class)
            ->where('is_deleted', false)
            ->where('activity_id', $activity_id)
            ->with('staff')
            ->with(['priceTier' => function ($query) {
                $query->select('id', 'advertised_price');
            }, 'activity' => function ($query) {
                $query->select('id', 'title');
                $query->addSelect(['base_price' => function ($query) {
                    $query->from('activity_pricing_infos')
                        ->whereColumn('activity_id', 'activities.id')
                        ->select('base_price')
                        ->limit(1);
                }]);
            }])
            ->paginate(10)
            ->onEachSide(1);

        $schedules->getCollection()->transform(function ($schedule) {
            $schedule['start_time'] = Carbon::parse($schedule['start_time'])->format('H:i');
            $schedule['end_time'] = Carbon::parse($schedule['end_time'])->format('H:i');

            return $schedule;
        });

        return DataResource::collection($schedules);

    }

    public function getNextSchedule($activity_id,$schedule_id): JsonResponse|DataResource
    {
        $activity = Activity::findOrFail($activity_id);

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
        $nextSchedule = Schedule::where('id', '>', $schedule_id)
            ->orderBy('id', 'asc')
            ->first();

        if (!$nextSchedule) {
            return new DataResource(['message' => 'No more schedules.']);
        }

        return new DataResource($nextSchedule);
    }

    public function getPreviousSchedule($activity_id,$schedule_id): JsonResponse|DataResource
    {
        $activity = Activity::findOrFail($activity_id);

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
        $nextSchedule = Schedule::where('id', '<', $schedule_id)
            ->orderBy('id', 'asc')
            ->first();

        if (!$nextSchedule) {
            return new DataResource(['message' => 'No more schedules.']);
        }

        return new DataResource($nextSchedule);
    }

    /**
     * Store a newly created resource in storage.
     * @param ActivityScheduleRequest $request
     * @param $activity_id
     * @return DataResource|JsonResponse
     */
    public function store(ActivityScheduleRequest $request, $activity_id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::findOrFail($activity_id);

            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            }

            $activity_name = $activity->title;

            $schedule_data = array_merge($request->validated(), ['is_published' => true]);
            $staff_members = $request->input('assigned_staff', []);

            if ($staff_members) {
                foreach ($staff_members as $member) {
                    $member_name = StaffMember::where('id', $member)->value('name');

                    if ($this->checkStaffAvailability($member, $request->date, $request->start_time, $request->end_time)) {
                        return response()->json(['message' => "$member_name is already assigned for $activity_name"], 422);
                    }

                    if ($request->location_id) {
                        $location = $request->input('location_id');

                        if ($this->checkLocationAvailability($location, $request->date, $request->start_time, $request->end_time, null)) {
                            return response()->json(['message' => 'This location is already occupied by another activity. Please select another location.'], 422);
                        }

                        $schedule = $activity->schedules()->create($schedule_data);
                    } else {
                        $schedule = $activity->schedules()->create($schedule_data);
                    }
                }
                $schedule->staff()->attach($staff_members);
                return new DataResource($schedule);
            } elseif ($request->location_id) {
                $location = $request->input('location_id');

                if ($this->checkLocationAvailability($location, $request->date, $request->start_time, $request->end_time, null)) {
                    return response()->json(['message' => 'This location is already occupied by another activity. Please select another location.'], 422);
                }

                $schedule = $activity->schedules()->create($request->validated());
                return new DataResource($schedule);
            } else {
                $schedule = $activity->schedules()->create($request->validated());
                return new DataResource($schedule);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     * @param $activity_id
     * @param $schedule_id
     * @return JsonResponse
     */
    public function destroy($activity_id, $schedule_id): JsonResponse
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            } else {
                $schedule = Schedule::findOrFail($schedule_id);
                if (!$schedule) {
                    return response()->json(['message' => 'Schedule not found'], 404);
                } else {
                    if (BookingItem::where('item_type', 'schedule')->where('item_id', $schedule_id)->first()) {
                        return response()->json(['message' => 'Schedule have bookings!'], 422);
                    }
                    $schedule->update(['is_deleted' => true]);
                    return response()->json(['message' => 'Schedule deleted successfully'], 200);
                }
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    /**
     * Update the specified resource in storage.
     * @param ActivityScheduleRequest $request
     * @param $activity_id
     * @param $schedule_id
     * @return DataResource | JsonResponse
     */
    public function update(ActivityScheduleRequest $request, $activity_id, $schedule_id): JsonResponse|DataResource
    {
        try {
            $request->merge(['is_published' => true]);
            $staff_members = $request->input('assigned_staff', []);

            $activity = Activity::findOrFail($activity_id);
            $activity_name = $activity->title;

            $schedule = Schedule::findOrFail($schedule_id);

            if ($request->location_id) {
                $location = $request->input('location_id');
                if ($this->checkLocationAvailability($location, $request->date, $request->start_time, $request->end_time,$schedule_id)) {
                    return response()->json(['message' => 'This location is already occupied by another activity. Please select another location.'], 422);
                }
            }

            $schedule_data = $request->except('assigned_staff');
            if(empty($staff_members)){
                $schedule->staff()->sync($staff_members);
            }
            if ($request->assigned_staff) {
                foreach ($staff_members as $member) {
                    $member_name = QueryBuilder::for(StaffMember::class)
                        ->select('name','id')
                        ->where('id', $member)
                        ->first();
                    if ($this->checkStaffAvailability($member, $request->date, $request->start_time, $request->end_time,$schedule_id)) {
                        return response()->json(['message' => $member_name->name.' is already assigned for '.$activity_name], 422);
                    }
                }
                $schedule->staff()->sync($staff_members);
            }

            $schedule->update($schedule_data);

            return new DataResource($schedule);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    //save as a draft

    public function save(DraftScheduleRequest $request, $activity_id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            $request->merge(['is_published' => false]);
            $activity_name = $activity->title;
            $staff_members = $request->input('assigned_staff', []);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            }
            else if ($request->assigned_staff) {
                foreach ($staff_members as $member) {
                    $member_name = QueryBuilder::for(StaffMember::class)
                        ->select('name')
                        ->where('id', $member)
                        ->first();
                    if ($this->checkStaffAvailability($member, $request->date, $request->start_time, $request->end_time,null,null)) {
                        $schedules=$activity->schedules()->create($request->except('assigned_staff'));
                        $schedules->staff()->attach($staff_members);
                        return response()->json(['message' => $member_name->name.' is already assigned for '.$activity_name], 200);
                    } else {
                        if ($request->location_id) {
                            $location = $request->input('location_id');
                            if ($this->checkLocationAvailability($location, $request->date, $request->start_time, $request->end_time,null)) {
                                $activity->schedules()->create($request->except('assigned_staff'));
                                return response()->json(['message' => 'This location is already occupied by another activity. Please select another location.'], 200);
                            } else {
                                $schedule=$activity->schedules()->create($request->except('assigned_staff'));
                            }
                        }
                        else{
                            $schedule = $activity->schedules()->create($request->except('assigned_staff'));
                        }
                    }
                }
                $schedule->staff()->attach($staff_members);
                return new DataResource($schedule);
            }
            elseif ($request->location_id){
                $location = $request->input('location_id');
                if ($this->checkLocationAvailability($location, $request->date, $request->start_time, $request->end_time)){
                    return response()->json(['message' => 'This location is already occupied by another activity. Please select another location.'], 200);
                }
                else{
                    $schedule = $activity->schedules()->create($request->validated());
                    return new DataResource($schedule);
                }
            }
            else {
                $schedule = $activity->schedules()->create($request->validated());
                return new DataResource($staff_members);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    //update and save as a draft
    public function updateDraft(DraftScheduleRequest $request, $activity_id, $schedule_id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::findOrFail($activity_id);
            $schedule = Schedule::findOrFail($schedule_id);
            $request->merge(['is_published' => false]);
            $activity_name = $activity->title;
            $staff_members = $request->input('assigned_staff', []);
            if (!$activity) {
                return response()->json(['message' => 'Activity not found'], 404);
            }
            else if ($request->assigned_staff) {
                foreach ($staff_members as $member) {
                    $member_name = QueryBuilder::for(StaffMember::class)
                        ->select('name','id')
                        ->where('id', $member)
                        ->first();
                    if ($this->checkStaffAvailability($member, $request->date, $request->start_time, $request->end_time,$schedule_id)) {
                        $schedule->update($request->except('assigned_staff'));
                        $schedule->staff()->sync($staff_members);
                        return response()->json(['message' => $member_name->name.' is already assigned for '.$activity_name], 200);
                    } else {
                        if ($request->location_id) {
                            $location = $request->input('location_id');
                            if ($this->checkLocationAvailability($location, $request->date, $request->start_time, $request->end_time,$schedule_id)) {
                                $schedule->update($request->except('assigned_staff'));
                                return response()->json(['message' => 'This location is already occupied by another activity. Please select another location.'], 200);
                            } else {
                                $schedule->update($request->except('assigned_staff'));
                            }
                        }
                        else{
                            $schedule->update($request->except('assigned_staff'));
                        }
                    }
                }
                $schedule->staff()->sync($staff_members);
                return new DataResource($schedule);
            }
            elseif ($request->location_id){
                $location = $request->input('location_id');
                if ($this->checkLocationAvailability($location, $request->date, $request->start_time, $request->end_time)){
                    return response()->json(['message' => 'This location is already occupied by another activity. Please select another location.'], 200);
                }
                else{
                    $schedule->update($request->validated());
                    return new DataResource($schedule);
                }
            }
            else {
                $schedule->update($request->validated());
                return new DataResource($staff_members);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    //reschedule
    public function reschedule(RescheduleRequest $request, $activity_id, $schedule_id): JsonResponse|DataResource
    {
        try {
            $activity = Activity::find($activity_id);
            if ($activity) {
                $schedule = Schedule::find($schedule_id);
                if ($schedule) {
                    $schedule_array = $schedule->toArray();
                    $schedule_array['date'] = $request->date;
                    $schedule_array['start_time'] = $request->start_time;
                    $schedule_array['end_time'] = $request->end_time;
                    $new_schedule = Schedule::create($schedule_array);
                    $schedule->update(['is_deleted' => true]);

                    $booking_items = BookingItem::where('item_id', $schedule->id)
                        ->where('item_type', 'schedule')->where('is_deleted', false)->with('booking')->get();
                    if ($booking_items->count() > 0) {
                        foreach ($booking_items as $item) {
                            //update booking item id to new id
                            $item->update(['item_id' => $new_schedule->id]);
                            $customer_data = $item->booking->customer;
                            $booking = $item->booking;
                            //send mail
                            $message = "
                                hi $customer_data->first_name
                                    According to your booking Ref: $booking->reference we are reschedule a activity from
                                 $schedule->date to $request->date.
                            ";
                            Mail::raw($message, function ($mail) use ($customer_data) {
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
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Activity not found!',
                ], 404);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
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

}
