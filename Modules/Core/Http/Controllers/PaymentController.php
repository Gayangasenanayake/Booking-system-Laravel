<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class PaymentController extends Controller
{

    private StripeClient $stripeClient;

    public function __construct()
    {
        $this->stripeClient = new StripeClient(ENV('STRIPE_API_KEY'));
    }

    public function setPayment(Request $request)
    {
        try {
            $user = auth('sanctum')
                ->user();
            $tenantId = $user->tenant_id;
            $tenantData = Tenant::where('id', $tenantId)
                ->first();
            $token = Str::random();
            if (!$tenantData->stripe_id) {
                $account = $this
                    ->stripeClient
                    ->accounts
                    ->create(
                        [
                            'country' => $request->country,
                            'type' => ENV('STRIPE_ACCOUNT_TYPE'),
                            'email' => $user->email,
                            'capabilities' => [
                                'card_payments' => [
                                    'requested' => true
                                ],
                                'transfers' => [
                                    'requested' => true
                                ]
                            ]
                        ]
                    );
                $tenantData
                    ->update(
                        [
                            'stripe_id' => $account->id,
                            'token' => $token,
                            'country' => $request->country
                        ]
                    );
            }

            $onboardLink = $this
                ->stripeClient
                ->accountLinks
                ->create(
                    [
                        'account' => $tenantData->stripe_id,
                        'refresh_url' => route(
                            'set.payment'
                        ),
                        'return_url' =>
                            'https://' . $user->tenant_id . '.betterbookings.dev/payment-connected?token=' . $token . '&tenant=' . $tenantData->id,
                        'type' => 'account_onboarding'
                    ]
                );





            return response()
                ->json(
                    [
                        'stripe_link' => $onboardLink->url,
                        'country' => $request->country
                    ]
                );
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()
                ->json(
                    [
                        'message' => 'Something went wrong. Please try again.'
                    ],
                    500
                );
        }
    }

    public function setPaymentSuccess($tenantId, $token)
    {
        try {
            $tenantData = Tenant::where('id', $tenantId)
                ->first();
            $tenantData
                ->update(
                    [
                        'is_stripe_onboarded' => true
                    ]
                );
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()
                ->json(
                    [
                        'message' => 'Something went wrong. Please try again.'
                    ],
                    500
                );
        }
    }
}
