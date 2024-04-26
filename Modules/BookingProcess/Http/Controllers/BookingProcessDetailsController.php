<?php

namespace Modules\BookingProcess\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataResource;
use App\Models\Tenant;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Booking\Entities\Booking;
use Spatie\QueryBuilder\QueryBuilder;

class BookingProcessDetailsController extends Controller
{
    public function show($tenant,$reference)
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $booked = QueryBuilder::for(Booking::class)
                ->where('reference', $reference)
                ->with(['bookingItems'])
                ->paginate(10);
            return DataResource::collection($booked);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function update($tenant,Request $request, $id)
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $pricing_info = Booking::where('id', $id)->firstOrFail();
            $pricing_info->update($request->all());
            return response()->json(['message' => 'Product pricing info updated successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }



    public function store(Request $request)
    {

    }


    public function edit($id)
    {

    }

    public function tenantInitialize($tenant): void
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
    }




}
