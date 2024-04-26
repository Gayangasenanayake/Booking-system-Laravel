<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Resources\DataResource;
use App\Models\Tenant;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Setting\Entities\Setting;
use Modules\Setting\Http\Requests\SettingMainInfoRequest;
use Spatie\QueryBuilder\QueryBuilder;
use Stripe\StripeClient;

class SettingController extends Controller
{

    private StripeClient $stripeClient;

    public function __construct(StripeClient $stripeClient)
    {
        $this->stripeClient = new StripeClient(ENV('STRIPE_API_KEY'));
    }

    /**
     * Display a listing of the resource.
     * @return DataResource|Renderable|JsonResponse
     */
    public function index(): Renderable|JsonResponse|DataResource
    {
        try {
            $setting = QueryBuilder::for(Setting::class)
                ->with('images')
                ->first();
            return new DataResource($setting);
        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return void
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     * @param SettingMainInfoRequest $request
     * @return DataResource|JsonResponse
     */
    public function store(SettingMainInfoRequest $request): JsonResponse|DataResource
    {
        try {
            $setting = Setting::create($request->except('logo'));
            if ($request->hasFile('logo')) {
                $width = 120;
                $height = 120;
                $file = $request->file('logo');
                $file_name = $file->getClientOriginalName();
                uploadImage($request->logo, '/setting/logo', $file_name, $width, $height);
                $setting->images()->create([
                    'imageable_id' => $setting->id,
                    'imageable_type' => Setting::class,
                    'collection' => 'logo',
                    'link' => 'setting/logo/'.$file_name,
                ]);
            }
            return new DataResource($setting);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param $business_id
     * @return void
     */
    public function show($business_id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('setting::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param SettingMainInfoRequest $request
     * @param $setting_id
     * @return DataResource|Renderable|JsonResponse
     */
    public function update(SettingMainInfoRequest $request, $setting_id): Renderable|JsonResponse|DataResource
    {
        try {
            $setting = Setting::findOrFail($setting_id);
            $new_setting = $setting->update($request->except('logo'));
            $current_image = $setting->images()->where('collection', 'logo')->first();
            if ($request->hasFile('logo')) {
                $width = 120;
                $height = 120;
                $file = $request->file('logo');
                $file_name = $file->getClientOriginalName();
                $file_name = $file_name . '_' . time() . '.' . $file->getClientOriginalExtension();
                if ($current_image) {
                    Storage::disk('s3')->delete($current_image->link);
                    $current_image->delete();
                }
                uploadImage($request->logo, '/setting/logo', $file_name, $width, $height);
                $setting->images()->create([
                    'imageable_id' => $setting->id,
                    'imageable_type' => Setting::class,
                    'collection' => 'logo',
                    'link' => 'setting/logo/' . $file_name
                ]);
            }else if($request->images && $current_image){
                $hasLogoImage = false;
                foreach ($request->images as $image){
                    if ($image['collection'] == 'logo'){
                        $hasLogoImage = true;
                        break;
                    }
                }
                if (!$hasLogoImage){
                    Storage::disk('s3')->delete($current_image->link);
                    $current_image->delete();
                }
            }else if(!$request->images && $current_image){
                Storage::disk('s3')->delete($current_image->link);
                $current_image->delete();
            }
            return new DataResource($setting);
        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {

    }

    public function getTenantData()
    {
        try {
            $user = Auth::user();
            $tenant = Tenant::where('id', $user->tenant_id)->first();

            if ($tenant->stripe_id) {
                $stripeAccount = $this->stripeClient->accounts->retrieve($tenant->stripe_id);
                if ($stripeAccount->capabilities->card_payments === "active") {
                    $data = [
                        'status' => true,
                        'country' => $tenant->country
                    ];
                } else {
                    $data = [
                        'status' => false,
                        'country' => $tenant->country
                    ];
                }
            } else {
                $data = [
                    'status' => false,
                    'country' => ''
                ];
            }

            return new DataResource($data);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['message'=>'something went wrong'],500);
        }

    }
}
