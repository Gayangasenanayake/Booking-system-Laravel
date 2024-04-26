<?php

namespace Modules\Customer\Http\Controllers;

use App\Http\Resources\DataResource;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Customer\Entities\Customer;
use Modules\Schedule\Entities\Schedule;
use Spatie\QueryBuilder\QueryBuilder;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $customers = QueryBuilder::for(Customer::class)
            ->with(['bookings' => function ($query) {
                $query->with('bookingItems.schedule.activity');
            }])
            ->allowedFilters('name', 'email')
            ->paginate(10)
            ->onEachSide(1);

        $customers->getCollection()->transform(function ($customer) {
            $customer['schedule_count'] = $customer->bookings
                ->flatMap(function ($booking) {
                    return $booking->bookingItems;
                })
                ->where('item_type', 'schedule')->count();
            $customer['total'] = $customer->bookings->sum('total');
            return $customer;
        });
        return DataResource::collection($customers);
    }


    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('customer::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param $user_id
     * @return DataResource
     */
    public function show($user_id): DataResource
    {
        $user = QueryBuilder::for(Customer::class)
            ->where('id',$user_id)
            ->with(['bookings.bookingItems.schedule.activity.images'])
            ->allowedFilters('name','email')
            ->first();
        return new DataResource($user);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('customer::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        //
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

    public function bookings($customer_id){

    }
}
