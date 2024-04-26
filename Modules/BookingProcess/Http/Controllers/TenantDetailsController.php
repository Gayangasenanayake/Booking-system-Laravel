<?php

namespace Modules\BookingProcess\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Setting\Entities\Setting;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class TenantDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function index($tenant): JsonResponse
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        $settings = Setting::with('images')
            ->first();
        $data = [
            'name' =>  $settings->business_name ?? '',
            'logo' =>
                    !empty($settings->images) && !empty($settings->images[0]) && $settings->images[0]->link ? $settings->images[0]->link : ''
        ];
        return response()
            ->json(
                [
                    'data' => $data
                ]
            );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('bookingprocess::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('bookingprocess::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('bookingprocess::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
