<?php

namespace Modules\Core\Http\Controllers;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Activity\Entities\ScheduleGroup;
use Modules\Booking\Entities\Booking;
use Modules\Customer\Entities\Customer;
use Modules\Product\Entities\Product;
use Modules\Schedule\Entities\Schedule;
use Modules\Widget\Entities\Widget;
use Spatie\QueryBuilder\QueryBuilder;

class CoreController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return array
     */
    public function index(): array
    {
        $current_date = Carbon::now()->toDateString();
        $previos_date = Carbon::now()->subDays(2)->toString();

        $Schedule_count = QueryBuilder::for(Schedule::class)
            ->where('date', $current_date)
            ->where('is_deleted', false)
            ->count();

        $Schedule_group_count = QueryBuilder::for(ScheduleGroup::class)
            ->where('from_date', '<=', $current_date)
            ->where('to_date', '>=', $current_date)
            ->where('is_deleted', false)
            ->count();

        $new_customers = QueryBuilder::for(Customer::class)
            ->whereDate('created_at', $current_date)
            ->whereDate('created_at', '>=', $previos_date)
            ->count();

        $sales = QueryBuilder::for(Booking::class)
            ->select(DB::raw('SUM(total) as total_sales'))
            ->get();
        $sales = $sales[0]->total_sales;

        $products_count = QueryBuilder::for(Product::class)
            ->where('is_deleted', false)
            ->count();

        return [
            'event_count' => $Schedule_count,
            'customer_count' => $new_customers,
            'sales' => $sales,
            'product_count' => $products_count,
        ];

    }


    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('core::create');
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('core::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('core::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function getWidgetData($widget_id,$tenant): JsonResponse
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);

        $widget = Widget::where('uuid', $widget_id)
            ->with(['activities' => function($query){
                $query->with(['images' => function ($query) {
                    $query->select('imageable_id')
                        ->selectRaw('COALESCE(
                MAX(CASE WHEN collection = "thumbnail_image" THEN  link END),
                MAX(CASE WHEN collection = "main_image" THEN link END)
            ) AS link')
                        ->groupBy('imageable_id');
                }]);
            }])
            ->first();
        return response()->json([
            'widget' => $widget,
        ]);
    }
}
