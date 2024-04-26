<?php

namespace Modules\Widget\Http\Controllers;

use App\Http\Resources\DataResource;
use App\Models\Tenant;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Activity\Entities\Activity;
use Modules\Setting\Entities\Setting;
use Modules\Widget\Entities\Widget;
use Modules\Widget\Http\Requests\WidgetStoreRequest;
use Spatie\QueryBuilder\QueryBuilder;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Stancl\Tenancy\Tenancy;
use Stripe\StripeClient;

class WidgetController extends Controller
{


    /** @var Tenancy */
    protected $tenancy;

    /** @var DomainTenantResolver */
    protected $resolver;
    private StripeClient $stripeClient;

    public function __construct(Tenancy $tenancy, DomainTenantResolver $resolver, StripeClient $stripeClient)
    {
        $this->tenancy = $tenancy;
        $this->resolver = $resolver;
        $this->stripeClient = new StripeClient(ENV('STRIPE_API_KEY'));
    }

    /**
     * Display a listing of the resource.
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $widgets = Widget::with('activities')->get();
        return DataResource::collection($widgets);
    }


    /**
     * Store a newly created resource in storage.
     * @param WidgetStoreRequest $request
     * @return JsonResponse
     * @throws TenantCouldNotBeIdentifiedException
     */
    public function store(WidgetStoreRequest $request): JsonResponse
    {
//        try {
            $user = Auth::user();
            $tenant = Tenant::where('id', $user->tenant_id)->first();
            $settings = Setting::first();
            if ($tenant->stripe_id) {
                foreach($request->activities as $activity){
                    $activity=Activity::find($activity);
                    if (!$activity->bookingSetting?->is_available_to_book){
                        return response()->json(['message' => 'You are not available to book.Look at activity booking setting!'], 422);
                    }
                    if (!$activity->bookingSetting?->calender_style){
                        return response()->json(['message' => 'Please select calendar type for bookings!'], 422);
                    }
                    if ($activity->pricingInfo->base_price <= 0){
                        return response()->json(['message' => 'Please set activity prices!'], 422);
                    }
                    if (!$settings?->business_name){
                        return response()->json(['message' => 'Please fill business setting data!'], 422);
                    }
                }
                $stripeAccount = $this->stripeClient->accounts->retrieve($tenant->stripe_id);
                if ($stripeAccount->capabilities->card_payments === "active") {
                    $widget = Widget::create($request->validated());
                    $widget->activities()->sync($request->input('activities'));
                    $script = "
<div id='root'>
<script>
    window.widgetProps = {
        app_key: '" . $widget->uuid . "',
        business: '" . tenant()->id . "'
    };
</script>
<script src='" . (ENV('APP_DOMAIN') == "localhost" ? 'http://localhost:3002/static/js/bundle.js' : 'https://widget.betterbookings.dev/dist/bundle.js')."'></script>
</div>
";
                    $widget->script = $script;
                    $widget->save();
                    return response()->json(['script' => $script], 201);
                }
            }
            return response()->json(['message' => 'Please complete stripe onboarding process first'], 422);
//        } catch (Exception $e) {
//            Log::error($e->getMessage());
//            return response()->json(['message' => 'Something went wrong'], 500);
//        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return DataResource
     */
    public function show($id): DataResource
    {
        $widget = QueryBuilder::for(Widget::class)
            ->where('id', $id)
            ->first();
        return new DataResource($widget);
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

    public function getWidgetScript()
    {
        // Your existing logic to generate the script
        $script = "
            (function () {
                var script = document.createElement('script');
                script.src = '" . asset('dist/bundle.js') . "';
                script.onload = function () {
                    window.widgetProps = {
                        app_key: 'YOUR_APP_KEY',
                        business: 'YOUR_BUSINESS_ID'
                    };
                };
                document.head.appendChild(script);
            })();
        ";
        return response($script)->header('Content-Type', 'application/javascript');
    }
}
