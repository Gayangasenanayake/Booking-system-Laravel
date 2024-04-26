<?php

namespace Modules\BookingProcess\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataResource;
use App\Models\Tenant;
use App\Traits\CommonFunctionTrait;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Modules\Schedule\Entities\Schedule;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class BookingProcessScheduleController extends Controller
{

    use CommonFunctionTrait;
    public function getSelectedScheduleForGivenMonth($tenant, $id, $month, $staffId)
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        try {
            $result = explode('-', $month);
            $year = $result[0];
            $month = $result[1];
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();

            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            $data = Schedule::where(function ($q) use ($staffId, $startDate, $endDate, $id) {
                $q->where('schedules.activity_id', $id);
                $q->whereBetween('schedules.date', [$startDate, $endDate]);
                $q->where('schedules.is_published', 1);
                if ($staffId != 0) {
                    $q->whereHas('staff', function ($r) use ($staffId) {
                        $r->where('staff_members.id', $staffId);
                    });
                }
            })->get();
            return new DataResource($data);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function getSelectedScheduleForGivenWeek($tenant, $id, $startDate, $staffId): JsonResponse|DataResource
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        try {
            $cleanedDate = preg_replace('/\s*\(.*?\)/', '', $startDate);
            $startDate = Carbon::parse($cleanedDate)->startOfWeek();
            $endDate = $startDate->copy()->addDays(6)->endOfDay();
//            $data = Schedule::where('activity_id', $id)
//                ->whereBetween('date', [$startDate, $endDate])
//                ->where('is_published', 1)->where('is_deleted', false)
//                ->with('staff')->with('location')->with('priceTier')
//                ->get();
            $data = Schedule::where(function ($q) use ($staffId, $startDate, $endDate, $id) {
                $q->where('schedules.activity_id', $id);
                $q->whereBetween('schedules.date', [$startDate, $endDate]);
                $q->where('schedules.is_published', 1);
                if ($staffId != 0) {
                    $q->whereHas('staff', function ($r) use ($staffId) {
                        $r->where('staff_members.id', $staffId);
                    });
                }
            })->get();
            return new DataResource($data);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function getSelectedScheduleForGivenDate($tenant, $id, $date, $staffId): JsonResponse|DataResource
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        try {
//            $data = Schedule::where('activity_id', $id)
//                ->where('date', $date)
//                ->where('is_published', 1)->where('is_deleted', false)
//                ->with('staff')->with('location')->with('priceTier')
//                ->get();
            $data = Schedule::where(function ($q) use ($staffId, $date, $id) {
                $q->where('schedules.activity_id', $id);
                $q->where('schedules.date', $date);
                $q->where('schedules.is_published', 1);
                if ($staffId != 0) {
                    $q->whereHas('staff', function ($r) use ($staffId) {
                        $r->where('staff_members.id', $staffId);
                    });
                }
            })->get();
            return new DataResource($data);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getPriceDetails($tenant, $scheduleId, $activityId, $month, $slotCount)
    {
        return $this->getActivityPriceDetails($tenant, $scheduleId, $activityId, $month, $slotCount);
    }

    public function getSelectedDateSchedules($tenant, $activityId, $date, $staffId = null)
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $query = Schedule::where(function ($q) use ($activityId, $date, $staffId) {
                $q->where('schedules.activity_id', $activityId)
                    ->where('schedules.date', $date)
                    ->where('schedules.is_published', 1);
                if ($staffId != 0) {
                    $q->whereHas('staff', function ($r) use ($staffId) {
                        $r->where('staff_members.id', $staffId);
                    });
                }
            });
            $data = $query->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->date,
                    'start_time' => $item->start_time,
                    'end_time' => $item->end_time,
                    'allocated_slots' => $item->allocated_slots,
                    'booked_slots' => $item->booked_slots,
                    'location' => $item->location && $item->location->name ? $item->location->name : '',
                ];
            });
            return new DataResource($data);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}
