<?php

namespace Modules\BookingProcess\Http\Controllers;

use App\Http\Resources\DataResource;
use App\Models\Tenant;
use App\Traits\BookingDetailsTrait;
use App\Traits\CommonFunctionTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Activity\Entities\Activity;
use Modules\Activity\Entities\ScheduleGroup;
use Modules\Booking\Entities\Booking;
use Modules\Booking\Http\Requests\BookingRequest;
use Modules\BookingProcess\Http\Requests\BookingsRequest;
use Modules\BookingProcess\Notifications\BookingConfirmationNotification;
use Modules\BookingProcess\Notifications\RescheduleBookingLoginNotification;
use Modules\Course\Entities\Course;
use Modules\Customer\Entities\Customer;
use Modules\Product\Entities\Product;
use Modules\Schedule\Entities\Schedule;
use Spatie\QueryBuilder\QueryBuilder;
use Stripe\StripeClient;

class BookingProcessController extends Controller
{
    use CommonFunctionTrait, BookingDetailsTrait;

    private StripeClient $stripeClient;

    public function __construct()
    {
        $this->stripeClient = new StripeClient('sk_test_8qjk5LGkoytPZfuqipWbPnnk');
    }


    public function index($tenant): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        $bookings = QueryBuilder::for(Booking::class)
            ->where('is_deleted', false)
            ->with(['bookingItems','booking_participants','customer:id,first_name,last_name,email,street,city,province'])
            ->allowedFilters(['status', 'date', 'time', 'schedules.'])
            ->paginate(10);
        return DataResource::collection($bookings);
    }

    public function store($tenant,Request $request)
    {
        $tenantData = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        $booking = 0;
        $transaction_fee = 1;
//        try {
            do {
                $reference = 'BB'.rand(000000,9999999);
            } while (Booking::where('reference', $reference)->exists());

            DB::transaction(function () use ($request,$transaction_fee,&$booking, $reference, $tenant) {
                $sub_total = 0.00;
                $total = 0.00;
                $percentage = 5;
                $productTotal=0.00;
                $productTax=0;
                $productServiceCharge=0;
                $productSubTotal=0;
                $productAllTotal=0;
                $productAllSubTotal=0;
                $productAllTaxTotal=0;
                $paid = 0;
                $customer = Customer::create($request->user_data);
                $booking = Booking::create(
                    [
                        'reference' => $reference,
                        'customer_id' => $customer->id,
                        'date' => Carbon::now()->format('Y-m-d'),
                        'time' => Carbon::now()->format('H:i'),
                        'participants' => $request->has('booking_participants') ? count($request->booking_participants) : 0,
                    ]
                );

                foreach ($request->booking_items as $item) {
                    if ($item['type'] === "schedule") {

                        $schedule = Schedule::findOrFail($item['id']);

                        $date = Carbon::parse($schedule->date);
                        $monthFormatted = $date->format('Y-m');


                        $schedule = Schedule::findOrFail($item['id']);
                        $activity = Activity::findOrFail($item['activity_id']);
                        if(($schedule->allocated_slots - $schedule->booked_slots) >= $item['number_of_slots']){

                            $priceDetails = $this->getActivityPriceDetails($tenant,$item['id'],$item['activity_id'],$monthFormatted, $item['number_of_slots']);
                            //calculate total price
                            $price = $activity->pricingInfo->base_price;
                            $total = $total + ($price * $item['number_of_slots']);
                            //update booked slots
                            $schedule->update(['booked_slots'=> $schedule->booked_slots + $item['number_of_slots']]);
                        }else{
                            return response()->json(['message' => 'No available slots'], 500);
                        }
                    } else if ($item['type'] === "product") {
                        //calculate total price
                        $qty=(int)$item['quantity'];
                        $product = Product::findOrFail($item['id']);
                        $price = $product->productPricingInfo->base_price;
                        $total = $total + ($price * $qty);
                        if($product->stock && $product->stock->available_stock >= $qty){
                            $product->stock()->update(['available_stock' => $product->stock->available_stock - $qty]);
                        }
                        $productServiceCharge=((ENV(
                                    'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                ) / 100) * ($price * $qty));
                        $productTax = ((ENV(
                                    'TAX_PERCENTAGE'
                                ) / 100) * ($price * $qty));
                        $productTotal = ($price * $qty) + $productServiceCharge + $productTax;
                        $productSubTotal = $price * $qty;
                        $productAllTotal+=$productTotal;
                        $productAllSubTotal+=$productSubTotal;
                        $productAllTaxTotal+=$productTax;
                    }

                    $booking->bookingItems()->create(
                        [
                            'item_type' => $item['type'],
                            'item_id' => $item['id'],
                            'booking_id' => $booking->id,
                            'number_of_slots' => $item['number_of_slots'] ?? null,
                            'quantity' => $qty ?? null,
                            'total' => $item['type'] === "product"?$productTotal:$priceDetails['final_total'],
                        ]
                    );
                }
                //store participants
                //if customer is a one participant
//                if ($request->booking_by_myself){
//                    $booking->participants()->create($request->user_data);
//                }
                if ($request->booking_participants) {
                    foreach ($request->booking_participants as $participant) {
                        $userDetails = json_encode($participant);

                        // Create a participant with user details stored as JSON in the 'details' column
                        $newParticipant = $booking->booking_participants()->create($participant);

                        // Update the 'details' column specifically for the newly created participant
                        $newParticipant->details = $userDetails;
                        $newParticipant->save();
                    }
                }

                $booking->update([
                    'sub_total' => $priceDetails['sub_total']+$productAllSubTotal,
                    'tax' => $priceDetails['tax']+$productAllTaxTotal,
                    'total' => round($priceDetails['final_total']+$productAllTotal,2),
                    'paid' => round($priceDetails['final_total']+$productAllTotal,2),
                ]);
            });

            return $this->continuePayment($tenant,$booking->paid*100, $reference);

            //confirmation mail

//            });
//            return response()->json(['message'=> 'Booking successfully completed','id'=>$booking->id]);
//        } catch (Exception $e) {
//            return response()->json(['message' => $e->getMessage()], 500);
//        }
    }

    public function show($tenant,$booking_id): JsonResponse|DataResource
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $booking = QueryBuilder::for(Booking::class)
                ->where('id', $booking_id)
                ->with(['bookingItems', 'booking_participants'])
                ->firstOrFail();

            foreach ($booking->bookingItems as $item) {
                if ($item->item_type == 'schedule') {
                    $schedule = Schedule::findOrFail($item->item_id);
                    $item->item_name = $schedule->activity->title;
                    $item->rate = $item->total / $item->number_of_slots;
                } elseif ($item->item_type == 'schedule_group') {
                    $schedule_group = ScheduleGroup::findOrFail($item->item_id);
                    $item->item_name = $schedule_group->activity->title;
                    $item->rate = $item->total / $item->number_of_slots;
                } elseif ($item->item_type == 'product') {
                    $product = Product::findOrFail($item->item_id);
                    $item->item_name = $product->title;
                    $item->rate = $item->total / $item->quantity;
                }
            }
            return new DataResource($booking);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function destroy($tenant,$booking_id): JsonResponse
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            DB::transaction(function () use ($booking_id) {
                $booking = Booking::findOrFail($booking_id);
                //reduce booking slots and restore product stock before delete booking items
                $items = $booking->bookingItems()->get();
                foreach ($items as $item){
                    if ($item['item_type'] === 'schedule'){
                        $schedule = Schedule::findOrFail($item['item_id']);
                        $schedule->update(['booked_slots' => $schedule->booked_slots - $item['number_of_slots']]);
                    }else if ($item['item_type'] === 'schedule_group'){
                        $schedule_group = ScheduleGroup::findOrFail($item['item_id']);
                        $schedule_group->update(['booked_slots' => $schedule_group->booked_slots - $item['number_of_slots']]);
                    }else if ($item['item_type'] === 'product'){
                        $product = Product::findOrFail($item['item_id']);
                        $product->stock()->update(['available_stock' => $product->available_stock + $item['quantity']]);
                    }
                }
                $booking->bookingItems()->update(['is_deleted'=>true]);
                $booking->booking_participants()->delete();
                $booking->update(['status' => 'Canceled']);

                //confirmation mail
                $message =
                    "
                    Hi ".$booking->customer->first_name."

                        your booking ".$booking->reference." was canceled without refund!
                ";
                Mail::raw($message, function ($mail) use ($booking){
                    $subject = "Booking Canceled";
                    $address = $booking->customer->email;
                    $mail->to($address);
                    $mail->subject($subject);
                });
            });

            return response()->json(['message' => 'Booking canceled successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update($tenant,BookingsRequest $request, $booking_id)
    {
//        try {
//            DB::transaction(function () use ($request, $booking_id) {
//                $total = 0;
//
//                $booking = Booking::findOrFail($booking_id);
//                $booking->update(
//                    [
//                        'date' => Carbon::now()->format('Y-m-d'),
//                        'time' => Carbon::now()->format('H:i:s'),
//                    ]
//                );
//                //reduce booking slots and restore product stock before delete booking items
//                $items = $booking->bookingItems()->get();
//                foreach ($items as $item){
//                    if ($item['item_type'] === 'schedule'){
//                        $schedule = Schedule::findOrFail($item['item_id']);
//                        $schedule->update(['booked_slots' => $schedule->booked_slots - $item['number_of_slots']]);
//                    }else if ($item['item_type'] === 'schedule_group'){
//                        $schedule_group = ScheduleGroup::findOrFail($item['item_id']);
//                        $schedule_group->update(['booked_slots' => $schedule_group->booked_slots - $item['number_of_slots']]);
//                    }else if ($item['item_type'] === 'product'){
//                        $product = Product::findOrFail($item['item_id']);
//                        $product->stock()->update(['available_stock' => $product->available_stock + $item['quantity']]);
//                    }
//                }
//                $booking->bookingItems()->delete();
//
//                foreach ($request->items as $item) {
//                    if ($item['type'] === "schedule") {
//                        //calculate total price
//                        $schedule = Schedule::findOrFail($item['id']);
//                        $price = $schedule->priceTier->price;
//                        $total = $total + ($price * $item['number_of_slots']);
//                        //update booked slots
//                        $schedule->update(['booked_slots'=> $schedule->booked_slots + $item['number_of_slots']]);
//
//                    }else if($item['type'] === "schedule_Group"){
//                        //calculate total price
//                        $scheduleGroup = ScheduleGroup::findOrFail($item['id']);
//                        $price = $scheduleGroup->priceTier->price;
//                        $total = $total + ($price * $item['number_of_slots']);
//                        //update booked slots
//                        $scheduleGroup->update(['booked_slots'=> $scheduleGroup->booked_slots + $item['number_of_slots']]);
//
//                    } else if ($item['type'] === "product") {
//                        //calculate total price
//                        $product = Product::findOrFail($item['id']);
//                        $price = $product->productPricingInfo->base_price;
//                        $total = $total + ($price * $item['number_of_slots']);
//                        //update available stock
//                        $product->stock()->update(['available_stock' => $product->available_stock - $item['quantity']]);
//
//                    }
//                    $booking->bookingItems()->create(
//                        [
//                            'item_type' => $item['type'],
//                            'item_id' => $item['id'],
//                            'booking_id' => $booking->id,
//                            'number_of_slots' => $item['number_of_slots'] ?? null,
//                            'quantity' => $item['quantity'] ?? null,
//                            'total' => $total ?? 0,
//                        ]
//                    );
//                }
//                //booking participants
//                $booking->participants()->delete();
//                if ($request->booking_participants){
//                    foreach ($request->booking_participants as $participant){
//                        $booking->participants()->create([
//                            'booking_id' =>  $booking->id,
//                            'name' => $participant['name'],
//                            'email' => $participant['email'],
//                            'age' => $participant['age'],
//                            'dietary_requirements' => $participant['dietary_requirements']
//                        ]);
//                    }
//                }
//            });
//        } catch (Exception $e) {
//            return response()->json(['message' => $e->getMessage()], 500);
//        }
        return response()->json(['message' => 'This process is not allowed!'], 500);
    }

    public function cancelRefund($tenant,$booking_id): JsonResponse
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            DB::transaction(function () use ($booking_id) {
                $booking = Booking::findOrFail($booking_id);
                //reduce booking slots and restore product stock before delete booking items
                $items = $booking->bookingItems()->get();
                foreach ($items as $item){
                    if ($item['item_type'] === 'schedule'){
                        $schedule = Schedule::findOrFail($item['item_id']);
                        $schedule->update(['booked_slots' => $schedule->booked_slots - $item['number_of_slots']]);
                    }else if ($item['item_type'] === 'schedule group'){
                        $schedule_group = ScheduleGroup::findOrFail($item['item_id']);
                        $schedule_group->update(['booked_slots' => $schedule_group->booked_slots - $item['number_of_slots']]);
                    }else if ($item['item_type'] === 'product'){
                        $product = Product::findOrFail($item['item_id']);
                        $product->stock()->update(['available_stock' => $product->available_stock + $item['quantity']]);
                    }
                }
                $booking->bookingItems()->update(['is_deleted'=>true]);
                $booking->booking_participants()->delete();
                $booking->update([
                    'status' => 'Refunded',
                    'is_refunded' => true
                ]);

                //refund process

                //confirmation mail
                $message =
                    "
                    Hi ".$booking->customer->first_name."

                        your booking ".$booking->reference." was canceled with refund!
                ";
                Mail::raw($message, function ($mail) use ($booking){
                    $subject = "Booking Canceled";
                    $address = $booking->customer->email;
                    $mail->to($address);
                    $mail->subject($subject);
                });
            });
            return response()->json(['message' => 'Booking canceled with refund!'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function paymentSuccess($tenant)
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
//        dd("d");
        dd(request()->all());
//        Log::error(request()->get('customer_id'));
    }

    public function continuePayment($tenant,$amount, $reference)
    {

        $tenantData = Tenant::find($tenant);
        tenancy()->initialize($tenant);
        $intentData = [
            'amount' => $amount,
            'currency' => 'nzd',
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            'transfer_data' => [
                'destination' => $tenantData->stripe_id
            ],
            'metadata' => [
                'reference' => $reference,
            ]
        ];

        $paymentIntent = $this->stripeClient->paymentIntents->create($intentData);
        return response()->json(['clientSecret' => $paymentIntent->client_secret, 'paymentIntent' => $paymentIntent]);
    }

    public function updateBookingConfirmation($tenant,$intentId)
    {
        $tenantData = Tenant::find($tenant);
        tenancy()->initialize($tenantData);
        $paymentIntent = $this->stripeClient->paymentIntents->retrieve($intentId);
        if ($paymentIntent->status === "succeeded"){

            $bookingData = Booking::where('reference', $paymentIntent->metadata->reference)->first();
            $bookingData->update([
                'status' => ENV('BOOKING_COMPLETED_STATE'),
            ]);

            $bookingData->customer->notify(
                new BookingConfirmationNotification($tenant, $bookingData->reference));





            $message =
                "
                    Hi ".$bookingData->customer->first_name."

                        your booking is completed!
                        Details:
                            Booking reference: ".$bookingData->reference."
                            Sub total: $".$bookingData->sub_total."
                            Tax: $".$bookingData->tax."
                            Processing fee: $".$bookingData->processing_fee."
                            Total: $".$bookingData->total."

                            Payment: $".$bookingData->paid."
                ";
//            Mail::raw($message, function ($mail) use ($bookingData) {
//                $subject = "Booking Confirmation";
//                $address = $bookingData->customer->email;
//                $mail->to($address);
//                $mail->subject($subject);
//            });
            return response()->json(['data' => $this->fetchBookedData($tenant, $paymentIntent->metadata->reference)], 200);
        }
    }

    public function fetchBookedData($tenant,$reference): DataResource
    {

        return new DataResource($this->getBookingDetails($tenant, $reference));
    }

    public function tenantInitialize($tenant): void
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
    }









}
