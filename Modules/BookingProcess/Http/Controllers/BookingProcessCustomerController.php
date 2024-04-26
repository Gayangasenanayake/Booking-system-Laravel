<?php

namespace Modules\BookingProcess\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataResource;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Customer\Entities\Customer;
use Spatie\QueryBuilder\QueryBuilder;

class BookingProcessCustomerController extends Controller
{
    public function index($tenant): AnonymousResourceCollection
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        $customers = QueryBuilder::for(User::class)
            ->with(['bookings' => function ($query) {
                $query->with('bookingItems:booking_id,item_type');
            }])
            ->allowedFilters('name', 'email')
            ->paginate(10);

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

    public function store($tenant,$request)
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            return Customer::create($request->all());
        } catch (Exception $e) {
            return $e;
        }
    }

    public function show($tenant,$user_id)
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        $user = QueryBuilder::for(User::class)
            ->where('id',$user_id)
            ->with(['bookings'])
            ->allowedFilters('name','email')
            ->first();
        return new DataResource($user);
    }
    public function tenantInitialize($tenant): void
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
    }

}
