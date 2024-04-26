<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Resources\DataResource;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Activity\Entities\ScheduleGroup;
use Modules\Booking\Entities\Booking;
use Modules\Booking\Http\Requests\BookingRequest;
use Modules\Booking\Http\Requests\RescheduleBookingRequest;
use Modules\Product\Entities\Product;
use Modules\Schedule\Entities\Schedule;
use Spatie\QueryBuilder\QueryBuilder;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $bookings = QueryBuilder::for(Booking::class)
            ->where('is_deleted', false)
            ->with(['bookingItems','booking_participants','customer:id,first_name,email,street,city,province','bookingItems.schedule.activity'])
            ->allowedFilters(['status', 'date', 'time', 'schedules.'])
            ->paginate(10)
            ->onEachSide(1);
        return DataResource::collection($bookings);
    }


    /**
     * Store a newly created resource in storage.
     * @param BookingRequest $request
     * @return JsonResponse
     */
    public function store(BookingRequest $request): JsonResponse
    {
        $booking_id = 0;
        try {
            DB::transaction(function () use ($request,&$booking_id) {
                $total = 0.00;
                $full_total = 0.00;
                $booking = Booking::create(
                    [
//                        'user_id' => Auth::id(),
//                        'user_id' => 1, //this use only for testing purpose
                        'date' => Carbon::now()->format('Y-m-d'),
                        'time' => Carbon::now()->format('H:i'),
                        'participants' => $request->has('booking_participants') ? count($request->booking_participants) : 0,
                    ]
                );
                $booking_id = $booking->id;
                foreach ($request->booking_items as $item) {
                    if ($item['type'] === "schedule") {
                        //calculate total price
                        $schedule = Schedule::findOrFail($item['id']);
                        $price = $schedule->priceTier->price;
                        $total = $total + ($price * $item['number_of_slots']);
                        //update booked slots
                        $schedule->update(['booked_slots'=> $schedule->booked_slots + $item['number_of_slots']]);

                    }else if($item['type'] === "schedule_group"){
                        //calculate total price
                        $scheduleGroup = ScheduleGroup::findOrFail($item['id']);
                        $price = $scheduleGroup->priceTier->price;
                        $total = $total + ($price * $item['number_of_slots']);
                        //update booked slots
                        $scheduleGroup->update(['booked_slots'=> $scheduleGroup->booked_slots + $item['number_of_slots']]);

                    } else if ($item['type'] === "product") {
                        //calculate total price
                        $product = Product::findOrFail($item['id']);
                        $price = $product->productPricingInfo->base_price;
                        $total = $total + ($price * $item['quantity']);
                        //update available stock
                        $product->stock()->update(['available_stock' => $product->available_stock - $item['quantity']]);
                    }
                    $booking->bookingItems()->create(
                        [
                            'item_type' => $item['type'],
                            'item_id' => $item['id'],
                            'booking_id' => $booking->id,
                            'number_of_slots' => $item['number_of_slots'] ?? null,
                            'quantity' => $item['quantity'] ?? null,
                            'total' => $total ?? 0,
                        ]
                    );
                    $full_total = $full_total + $total;
                }
                //store participants
                if ($request->booking_participants){
                    foreach ($request->booking_participants as $participant){
                        $booking->booking_participants()->create([
                            'booking_id' =>  $booking->id,
                            'name' => $participant['name'],
                            'email' => $participant['email'],
                            'age' => $participant['age'],
                            'dietary_requirements' => $participant['dietary_requirements']
                        ]);
                    }
                }
                $booking->update(['total'=>$full_total]);

                //payment process

            });
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
        return response()->json(['message'=> 'Booking successfully completed','id'=>$booking_id]);
    }

    /**
     * Show the form for editing the specified resource.
     * @param $booking_id
     * @return DataResource|JsonResponse
     */
    public function show($booking_id): JsonResponse|DataResource
    {
        try {
            $booking = QueryBuilder::for(Booking::class)
                ->where('id', $booking_id)
                ->with(['bookingItems', 'booking_participants','customer','bookingItems.schedule.activity.pricingInfo','bookingItems.product.productPricingInfo'])
                ->firstOrFail();

            foreach ($booking->bookingItems as $item) {
                if ($item->item_type == 'schedule') {
                    $schedule = QueryBuilder::for(Schedule::class)->where('id',$item->item_id)->with(['activity'])->first();
                    $item->item_name = $schedule->activity->title;
                    $item->rate = $item->total / $item->number_of_slots;
                } elseif ($item->item_type == 'product') {
                    $product = QueryBuilder::for(Product::class)->where('id',$item->item_id)->first();
                    $item->item_name = $product->title;
                    $item->rate = $item->total / $item->quantity;
                }
            }

            $processingFee = (ENV('BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE') / 100) * $booking->sub_total;
            $booking->processing_fee = $processingFee;

            return new DataResource($booking);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param $booking_id
     * @return JsonResponse
     */
    public function destroy($booking_id): JsonResponse
    {
        try {
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
            });
            return response()->json(['message' => 'Booking deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param BookingRequest $request
     * @param $booking_id
     * @return JsonResponse
     */
    public function update(BookingRequest $request, $booking_id)
    {
        try {
            DB::transaction(function () use ($request, $booking_id) {
                $total = 0;

                $booking = Booking::findOrFail($booking_id);
                $booking->update(
                    [
                        'user_id' => Auth::id(),
                        'date' => Carbon::now()->format('Y-m-d'),
                        'time' => Carbon::now()->format('H:i:s'),
                    ]
                );
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
                $booking->bookingItems()->delete();

                foreach ($request->items as $item) {
                    if ($item['type'] === "schedule") {
                        //calculate total price
                        $schedule = Schedule::findOrFail($item['id']);
                        $price = $schedule->priceTier->price;
                        $total = $total + ($price * $item['number_of_slots']);
                        //update booked slots
                        $schedule->update(['booked_slots'=> $schedule->booked_slots + $item['number_of_slots']]);

                    }else if($item['type'] === "schedule_Group"){
                        //calculate total price
                        $scheduleGroup = ScheduleGroup::findOrFail($item['id']);
                        $price = $scheduleGroup->priceTier->price;
                        $total = $total + ($price * $item['number_of_slots']);
                        //update booked slots
                        $scheduleGroup->update(['booked_slots'=> $scheduleGroup->booked_slots + $item['number_of_slots']]);

                    } else if ($item['type'] === "product") {
                        //calculate total price
                        $product = Product::findOrFail($item['id']);
                        $price = $product->productPricingInfo->base_price;
                        $total = $total + ($price * $item['number_of_slots']);
                        //update available stock
                        $product->stock()->update(['available_stock' => $product->available_stock - $item['quantity']]);

                    }
                    $booking->bookingItems()->create(
                        [
                            'item_type' => $item['type'],
                            'item_id' => $item['id'],
                            'booking_id' => $booking->id,
                            'number_of_slots' => $item['number_of_slots'] ?? null,
                            'quantity' => $item['quantity'] ?? null,
                            'total' => $total ?? 0,
                        ]
                    );
                }
                //booking participants
                $booking->booking_participants()->delete();
                if ($request->booking_participants){
                    foreach ($request->booking_participants as $participant){
                        $booking->booking_participants()->create([
                            'booking_id' =>  $booking->id,
                            'name' => $participant['name'],
                            'email' => $participant['email'],
                            'age' => $participant['age'],
                            'dietary_requirements' => $participant['dietary_requirements']
                        ]);
                    }
                }
            });
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    //cancel booking
    public function cancelRefund($booking_id): JsonResponse
    {
        try {
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
            });
            return response()->json(['message' => 'Booking canceled with refund!'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    //reschedule booking
    public function reschedule(RescheduleBookingRequest $request, $booking_id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($booking_id);
            if ($booking){
                $new_booking_id = null;
                DB::transaction(function () use ($booking_id,$booking,$request, &$new_booking_id) {
                    $new_booking = Booking::create([
                        'user_id' => $booking->user_id,
                        'date' => $request->date,
                        'time' => $request->time,
                        'reference' => $booking->reference,
                        'participants' => $booking->booking_participants,
                        'paid' => $booking->paid,
                        'total' => $booking->total,
                        'status' => 'Confirmed', //bcz reschedule by admin
                    ]);
                    $new_booking_id = $new_booking->id;
                    $booking->update(['status' => 'Rescheduled']); //update previous booking status
                    //update booking items booking_id
                    $items = $booking->bookingItems()->get();
                    foreach ($items as $item) {
                        $item->update(['booking_id' => $new_booking->id]);
                    }
                    $members = $booking->booking_participants()->get();
                    foreach ($members as $item) {
                        $item->update(['booking_id' => $new_booking->id]);
                    }
                });
                return response()->json(['message'=> 'Booking successfully rescheduled','id'=>$new_booking_id]);
            }else{
                return response()->json(['message' => 'Booking not found!'], 404);
            }
        }catch (Exception $e ){
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
