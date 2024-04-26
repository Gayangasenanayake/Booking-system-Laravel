<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\DataResource;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Schedule\Entities\Schedule;
use Modules\Staff\Entities\StaffMember;
use Modules\Staff\Http\Requests\StaffMemberRequest;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(): JsonResponse|AnonymousResourceCollection
    {
        try {
            $staff_members = QueryBuilder::for(StaffMember::class)
                ->with(['images'=>function($query){
                    $query->where('collection','=','avatar');
                }])
                ->with('schedules')
                ->paginate(10)
                ->onEachSide(1);
            return DataResource::collection($staff_members);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function staffInfo(): JsonResponse|DataResource
    {
        try {
            $activities = QueryBuilder::for(StaffMember::class)
                ->select('id', 'name')
                ->where('is_deleted', false)
                ->allowedFilters('name')
                ->get();
            return new DataResource($activities);
        } catch (Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param StaffMemberRequest $request
     * @return DataResource|JsonResponse
     */
    public function store(StaffMemberRequest $request): JsonResponse|DataResource
    {
        try {
            $staff_member = StaffMember::create($request->except('avatar'));

            if ($request->hasFile('avatar')) {
                $width = 120;
                $height = 120;
                $file = $request->file('avatar');
                $file_name = $file->getClientOriginalName();
                $file_name = $file_name . '_' . time() . '.' . $file->getClientOriginalExtension();
                uploadImage($request->avatar, '/staff/avatar', $file_name, $width, $height);
                $staff_member->images()->create([
                    'imageable_id' => $staff_member->id,
                    'imageable_type' => StaffMember::class,
                    'collection' => 'avatar',
                    'link' => 'staff/avatar/' . $file_name,
                ]);
            }
            return new DataResource($staff_member);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show($staff_member_id): JsonResponse|DataResource
    {
        try {
            $staff_member = QueryBuilder::for(StaffMember::class)
                ->where('id', $staff_member_id)
                ->with('schedules')
                ->with('images')
                ->firstOrFail();
            return new DataResource($staff_member);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function favouriteMember(): DataResource
    {
        $currentMonth = Carbon::now()->month;
        $previousMonth = Carbon::now()->subMonth()->month;
        $favour = DB::table("staff_member_schedules")
            ->select('staff_member_id')
            ->join('schedules', 'staff_member_schedules.schedule_id', '=', 'schedules.id')
            ->whereMonth(DB::raw("schedules.date"), $currentMonth)
            ->groupBy('staff_member_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(1)
            ->value('staff_member_id');

        if (!$favour) {
            $staffMemberIdWithMaxOccurrence = DB::table("staff_member_schedules")
                ->select('staff_member_id')
                ->join('schedules', 'staff_member_schedules.schedule_id', '=', 'schedules.id')
                ->whereMonth(DB::raw("schedules.date"), $currentMonth)
                ->groupBy('staff_member_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(1)
                ->value('staff_member_id');

            $modifiedStaffName = DB::table("staff_members")
                ->where('id', $staffMemberIdWithMaxOccurrence)
                ->value('name');

            return new DataResource([
                'id' => $staffMemberIdWithMaxOccurrence,
                'name' => $modifiedStaffName,
            ]);
        }

        $modifiedStaffName = DB::table("staff_members")
            ->where('id', $favour)
            ->value('name');

        return new DataResource([
            'id' => $favour,
            'name' => $modifiedStaffName,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param $staff_member_id
     * @return JsonResponse
     */
    public function destroy($staff_member_id): JsonResponse
    {
        try {
            StaffMember::findOrFail($staff_member_id)->update(['is_deleted', true]);
            return response()->json(['message' => 'Staff member deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param StaffMemberRequest $request
     * @param $staff_member_id
     * @return DataResource|JsonResponse
     */
    public function update(StaffMemberRequest $request, $staff_member_id): JsonResponse|DataResource
    {
        try {
            $staff_member = StaffMember::findOrFail($staff_member_id);
            $staff_member->update($request->except('avatar'));
            $current_image = $staff_member->images()->where('collection', 'avatar')->first();
            if ($request->hasFile('avatar')) {
                $width = 120;
                $height = 120;
                $file = $request->file('avatar');
                $file_name = $file->getClientOriginalName();
                $file_name = $file_name . '_' . time() . '.' . $file->getClientOriginalExtension();
                if ($current_image) {
                    Storage::disk('s3')->delete($current_image->link);
                    $current_image->delete();
                }
                uploadImage($request->avatar, '/staff/avatar', $file_name, $width, $height);
                $staff_member->images()->create([
                    'imageable_id' => $staff_member->id,
                    'imageable_type' => StaffMember::class,
                    'collection' => 'avatar',
                    'link' => 'staff/avatar/' . $file_name
                ]);
            }else if(!$request->images && $current_image){
                Storage::disk('s3')->delete($current_image->link);
                $current_image->delete();
            }

            return new DataResource($staff_member);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function viewSchedule(): AnonymousResourceCollection
    {
        $currentDateTime = Carbon::now();
        $schedules = QueryBuilder::for(Schedule::class)
            ->with('activity')
            ->where(function ($query) use ($currentDateTime) {
                $query->where('date', '>', $currentDateTime->toDateString())
                    ->orWhere(function ($query) use ($currentDateTime) {
                        $query->whereDate('date', $currentDateTime->toDateString())
                            ->whereTime('start_time', '>=', $currentDateTime->toTimeString());
                    });
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate(10);


        $schedules->each(function ($schedule) {
            $schedule->date = Carbon::parse($schedule->date)->isoFormat('Do MMMM YYYY');
        });
        return DataResource::collection($schedules);
    }

    public function viewScheduleList(): JsonResponse|DataResource
    {
        try {
            $currentDateTime = Carbon::now();
            $schedules = QueryBuilder::for(Schedule::class)
                ->with('activity')
                ->where(function ($query) use ($currentDateTime) {
                    $query->where('date', '>', $currentDateTime->toDateString())
                        ->orWhere(function ($query) use ($currentDateTime) {
                            $query->whereDate('date', $currentDateTime->toDateString())
                                ->whereTime('start_time', '>=', $currentDateTime->toTimeString());
                        });
                })
                ->orderBy('date')
                ->orderBy('start_time')
                ->allowedFilters(['activity.title'])
                ->get();
            $schedules->each(function ($schedule) {
                $schedule->date = Carbon::parse($schedule->date)->isoFormat('Do MMMM YYYY');
            });
            return new DataResource($schedules);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function addSchedule($staff_member_id, \Illuminate\Http\Request $request): JsonResponse|DataResource
    {
        try {
            $request->validate([
                'activities' => 'required'
            ]);
            $staffMember = StaffMember::find($staff_member_id);

            foreach ($request->activities as $schedule_id){
                $schedule = Schedule::find($schedule_id);
                $isAlreadyAssigned = DB::table('staff_member_schedules')
                    ->where('staff_member_id', $staff_member_id)
                    ->where('schedule_id', $schedule_id)
                    ->exists();
                if (!$staffMember) {
                    return response()->json(['message' => 'Staff member not found'], 404);
                } else if (!$schedule) {
                    return response()->json(['message' => 'schedule not found'], 404);
                } else if ($isAlreadyAssigned) {
                    return response()->json(['message' => 'schedule already assigned'], 404);
                } else {
                    DB::table('staff_member_schedules')->insert([
                        'staff_member_id' => $staff_member_id,
                        'schedule_id' => $schedule_id,
                    ]);
                    $this->showSchedule($staff_member_id);
                }
            }
            $redirectUrl ="/staff/member/edit?id=$staff_member_id";
            return new DataResource(['redirect_url' => $redirectUrl]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong!'], 500);
        }
    }

    public function showSchedule($staff_member_id): JsonResponse|AnonymousResourceCollection
    {
        try {
            $staff = StaffMember::findOrFail($staff_member_id);
            if (!$staff) {
                return response()->json(['message' => 'staff member not found']);
            } else {
//                $schedules=DB::table('schedules as s')
//                    ->join('activities as a', 'a.id', '=', 's.activity_id')
//                    ->where(function ($query) use ($staff_member_id) {
//                        $query->whereIn('s.id', function ($subQuery) use ($staff_member_id) {
//                            $subQuery->select('schedule_id')
//                                ->from('staff_member_schedules')
//                                ->where('staff_member_id', '=', $staff_member_id);
//                        })->where('s.date', '>=', DB::raw('CURDATE()'))
//                            ->orWhere(function ($query) {
//                                $query->where('s.date', '=', DB::raw('CURDATE()'))
//                                    ->where('s.start_time', '>=', DB::raw('CURTIME()'));
//                            });
//                    })
//                    ->orderBy('s.date')
//                    ->orderBy('s.start_time')
//                    ->select('a.title', 's.date', 's.start_time', 's.end_time','s.allocated_slots','s.id')
//                    ->paginate(10);
                $currentDateTime = Carbon::now();
                $schedules = QueryBuilder::for(Schedule::class)
                    ->with('activity')
                    ->join('staff_member_schedules', 'schedules.id', '=', 'staff_member_schedules.schedule_id')
                    ->where('is_deleted',false)
                    ->where('staff_member_schedules.staff_member_id', $staff_member_id)
                    ->where(function ($query) use ($currentDateTime) {
                        $query->where('date', '>', $currentDateTime->toDateString())
                            ->orWhere(function ($query) use ($currentDateTime) {
                                $query->whereDate('date', $currentDateTime->toDateString())
                                    ->whereTime('start_time', '>=', $currentDateTime->toTimeString());
                            });
                    })
                    ->whereHas('activity', function ($query) {
                        $query->where('is_selecting_staff', true);
                    })
                    ->allowedFilters('activity_id','location_id','date')
                    ->orderBy('date')
                    ->orderBy('start_time')
                    ->paginate(10);

                $schedules->each(function ($schedule) {
                    $schedule->date = Carbon::parse($schedule->date)->isoFormat('Do MMMM YYYY');
                });
                return DataResource::collection($schedules);

            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function deleteSchedule($staff_member_id, $schedule_id): JsonResponse
    {
        try {
            $staffMember = StaffMember::findOrFail($staff_member_id);
            if (!$staffMember) {
                return response()->json(['message' => 'Staff member not found'], 404);
            }
            DB::table('staff_member_schedules')
                ->where('staff_member_id', $staff_member_id)
                ->where('id', $schedule_id)
                ->delete();
            return response()->json(['message' => 'Schedule deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function checkStaffAvailability($member, $date, $start_time, $end_time, $schedule_id = null): JsonResponse
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
            ->whereHas('staff', function ($query) use ($member) {
                $query->where('staff_member_id', $member);
            });

        if (!is_null($schedule_id)) {
            $query->where('id', '!=', $schedule_id);
        }

        if ($query->exists()) {
            return response()->json(['message' => 'Staff member is already assigned to another schedule.'], 200);
        }

        return response()->json(['message' => 'Staff member available.'], 200);
    }

    public function checkStaffAvailabilityInRange($member, $from_date, $to_date, $start_time, $end_time,$busy_days): JsonResponse
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
                $query->whereRaw("FIND_IN_SET(DAYNAME(date), ?) > 0", [$busy_days]);
            });


        if ($query->exists()) {
            return response()->json(['message' => 'Staff member is already assigned to another schedule.'], 200);
        }

        return response()->json(['message' => 'Staff member available within the specified date range.'], 200);
    }
}
