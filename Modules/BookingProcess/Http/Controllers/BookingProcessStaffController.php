<?php

namespace Modules\BookingProcess\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataResource;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Staff\Entities\StaffMember;
use Spatie\QueryBuilder\QueryBuilder;

class BookingProcessStaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($tenant)
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $staffs = QueryBuilder::for(StaffMember::class)
                ->where('is_deleted',false)
                ->allowedFilters(['id','name','title','experience','profile_data'])
                ->select(['id','name','title','experience','profile_data'])
                ->paginate(3);

            return DataResource::collection($staffs);
        } catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }

    public function staffSchedules($tenant,$member_id)
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        try {
            $staff = StaffMember::findOrFail($member_id);
            if ($staff)
            {
                $schedules_data = null;
                $scheduleGroups_data = null;
                $start_date = Carbon::now();
                $end_date = $start_date->copy()->addDays(7);
                if($staff->schedules()->exists()){
                    $schedules_data = $staff->schedules()->whereBetween('date',[$start_date,$end_date])
                        ->where('schedules.is_deleted',false)
                        ->where('schedules.is_published',true)
                        ->select(['schedules.id','date','start_time','allocated_slots','booked_slots'])
                        ->get();
                }
                $data_collection = collect(['schedules'=> $schedules_data])->filter();
                return DataResource::collection($data_collection);
            }
            return response()->json(['message'=>''],500);
        } catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage()],500);
        }
    }

    public function tenantInitialize($tenant): void
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
    }

    public function create()
    {

    }


    public function store(Request $request)
    {
        //
    }


    public function show($id)
    {

    }


    public function edit($id)
    {

    }


    public function update(Request $request, $id)
    {

    }


    public function destroy($id)
    {
        //
    }
}
