<?php

namespace Modules\BookingProcess\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataResource;
use App\Models\Tenant;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Activity\Entities\Activity;
use Modules\Product\Entities\Product;
use Modules\Staff\Entities\StaffMember;
use Spatie\QueryBuilder\QueryBuilder;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class BookingProcessActivityController extends Controller
{
    public function index()
    {

    }

    public function create()
    {

    }

    public function store(Request $request)
    {

    }

    public function show($tenant,$activity_id): JsonResponse|DataResource
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $course = QueryBuilder::for(Activity::class)
                ->where('id', $activity_id)
                ->with(['priceTiers','tags','pricingInfo','prerequisites','images','products','bookingSetting'])
                ->with(['schedules' => function ($query) {
                    $query->with('staff');
                }])
                ->first();
            $course['process_fee_percentage'] = ENV('BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE');
            return new DataResource($course);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function tenantInitialize($tenant){
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
    }

    public function edit($id)
    {

    }


    public function update(Request $request, $id)
    {

    }


    public function destroy($id)
    {

    }


    /**
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function getActivityStaff($tenant, $id): DataResource
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        $activity = Activity::findOrFail($id);
        $staffs = [];
        foreach ($activity->schedules as $schedule) {
            if ($schedule->staff !== null) {
                foreach ($schedule->staff as $staff) {
                    // Collect staff details in an associative array using staff ID as the key
                    $staffs[$staff->id] = [
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'title' => $staff->title,
                        'profile_data'=>$staff->profile_data,
                        'images' => $staff->images,
                    ];
                }
            }
        }
        // Retrieve unique staff details by using array values
        $uniqueStaffs = array_values($staffs);
        return new DataResource($uniqueStaffs);
    }

    public function getActivityProductsById($tenant, $activityId)
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $activity = Activity::findOrFail($activityId);
            $productDetails = [];
            $products = $activity->products;
            foreach ($products as $product) {
                $productDetails[] = [
                    'id' => $product->id,
                    'title' => $product->title,
                    'sku' => $product->sku,
                    'brief_description' => strip_tags($product->brief_description ?? ''),
                    'price' => $product->productPricingInfo ?? '',
                    'images' =>  $product->images ?? '',
                ];
            }
            return new DataResource($productDetails);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
