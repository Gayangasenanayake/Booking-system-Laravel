<?php

namespace Modules\BookingProcess\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Traits\BookingDetailsTrait;
use App\Traits\CommonFunctionTrait;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Activity\Entities\ScheduleGroup;
use Modules\Booking\Entities\Booking;
use Modules\BookingProcess\Http\Requests\BookingRescheduleLoginRequest;
use Faker\Factory as Faker;
use Modules\BookingProcess\Http\Requests\YourDetailUpdateRequest;
use Modules\BookingProcess\Notifications\BookingDateChangeNotification;
use Modules\BookingProcess\Notifications\BookingDetalsChangeNotification;
use Modules\BookingProcess\Notifications\RescheduleBookingLoginNotification;
use Modules\Product\Entities\Product;
use Modules\Schedule\Entities\Schedule;

use function Webmozart\Assert\Tests\StaticAnalysis\integer;

class BookingProcessRescheduleController extends Controller
{

    use CommonFunctionTrait, BookingDetailsTrait;

    public function index()
    {
    }

    public function store(Request $request)
    {
    }

    public function show($id)
    {
    }

    public function edit($id)
    {
    }

    public function destroy($id)
    {
    }

    public function login($tenant, BookingRescheduleLoginRequest $request): JsonResponse
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);

        try {
            $bookingReference = $request->reference;
            $reference = Booking::where('reference', $bookingReference)->first();

            if (!$reference) {
                return response()->json(['message' => 'Invalid Reference'], 422);
            } else {
                $customer = $reference->customer;
                $email = $customer->email;
                $verificationCode = $this->verificationCode($bookingReference, $tenant);
                $simpleString = strtolower(str_replace(' ', '-', $verificationCode));
                $reference->verification_code=$simpleString;
                $reference->save();
                $customer->notify(
                    new RescheduleBookingLoginNotification($email, $simpleString, $tenant->id, $request->reference)
                );
                return response()->json(
                    ['data' => ['email' => $reference->customer->email, 'code' => $verificationCode]],
                    200
                );
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function verificationCode($bookingReference, $tenant): string
    {
        $faker = Faker::create();
        $two_words = explode(' ', $faker->sentence(3, true));
        $verificationCode = $two_words[0] . ' ' . $two_words[1];
        return $verificationCode;
    }

    public function create()
    {
    }

    public function validateBookingReference($tenant, Request $request)
    {
        try {
            $tenantData = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $booking = Booking::where('reference', $request->reference)
                ->where('status', ENV('BOOKING_COMPLETED_STATE'))
                ->where('is_refunded', ENV('BOOKING_REFUNDED_FALSE'))
                ->with(['bookingItems.schedule.activity', 'customer','booking_participants'])
                ->first();
            if ($booking && !empty($booking->verification_code)) {
                if ($booking->verification_code === $request->verification) {
                    return response()->json(['data' => $this->setBookingData($tenant, $request->reference, $booking)], 200);
                } else {
                    return response()->json(['message' => 'Invalid booking reference'], 422);
                }
            } else {
                return response()->json(['message' => 'Booking reference not found'], 404);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function fetchUpdatedBookedData($tenant, $reference)
    {
        try {
            Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $bookingDetails = Booking::where('reference', $reference)
                ->first();
            return response()->json(['data' => $this->setBookingData($tenant, $reference, $bookingDetails)], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    function setBookingData($tenant, $reference, $bookingDetails)
    {
        $data = $this->getBookingDetails($tenant, $reference);
        $data['reference'] = $reference;
        $data['customer'] = $bookingDetails->customer;
        $data['slot_count'] = $bookingDetails->participants;
        $data['participants'] = $bookingDetails->booking_participants;
        $data['activity_images'] = $bookingDetails->bookingItems[0]->schedule->activity->images;
        return $data;
    }

    public function cancelBookingRequest($tenant, $reference)
    {
        try {
            $tenantData = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $booking = Booking::where('reference', $reference)->first();
            $currentDate = new DateTime();
            $targetDate = new DateTime($booking->bookingItems[0]->schedule->date.' '.$booking->bookingItems[0]->schedule->start_time);
            $interval = $currentDate->diff($targetDate);
            $hoursDifference = $interval->days * 24 + $interval->h;
                if ($booking->bookingItems[0]->schedule->cancel_lead_time < $hoursDifference) {
                    $items = $booking->bookingItems()->get();
                    foreach ($items as $item) {
                        if ($item['item_type'] === 'schedule') {
                            $schedule = Schedule::findOrFail($item['item_id']);
                            $schedule->update(['booked_slots' => $schedule->booked_slots - $item['number_of_slots']]);
                        } else {
                            if ($item['item_type'] === 'schedule group') {
                                $schedule_group = ScheduleGroup::findOrFail($item['item_id']);
                                $schedule_group->update(
                                    ['booked_slots' => $schedule_group->booked_slots - $item['number_of_slots']]
                                );
                            } else {
                                if ($item['item_type'] === 'product') {
                                    $product = Product::findOrFail($item['item_id']);
                                    $product->stock()->update(
                                        ['available_stock' => $product->available_stock + $item['quantity']]
                                    );
                                }
                            }
                        }
                    }
                    $booking->bookingItems()->update(['is_deleted' => true]);
                    $booking->booking_participants()->delete();
                    $booking->update([
                        'status' => 'Refunded',
                        'is_refunded' => true
                    ]);

                    //refund process

                    //confirmation mail
                    $message =
                        "
                    Hi " . $booking->customer->first_name . "

                        your booking " . $booking->reference . " was canceled with refund!
                ";
                    Mail::raw($message, function ($mail) use ($booking) {
                        $subject = "Booking Canceled";
                        $address = $booking->customer->email;
                        $mail->to($address);
                        $mail->subject($subject);
                    });
                    return response()->json(['message' => 'Booking canceled with refund!'], 200);
                } else {
                    return response()
                        ->json(
                            [
                                'message' => 'You can not cancel booking date after ' . $booking->bookingItems[0]->schedule->change_lead_time . ' hours'
                            ],
                            422);
                }

        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function update(Request $request, $id)
    {
    }

    public function getBookingDateDetails($tenant, $reference)
    {
        try {
            $tenantData = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $booking = Booking::where('reference', $reference)->first();
            if ($booking) {
                $currentDate = new DateTime();
                $targetDate = new DateTime($booking->bookingItems[0]->schedule->date.' '.$booking->bookingItems[0]->schedule->start_time);
                $interval = $currentDate->diff($targetDate);
                $hoursDifference = $interval->days * 24 + $interval->h;
//                dd($hoursDifference);
                if ($booking->bookingItems[0]->schedule->change_lead_time < $hoursDifference) {
                    $data = [
                        'schedule_id' => $booking
                            ->bookingItems[0]
                            ->schedule
                            ->id,
                        'activity_id' => $booking
                            ->bookingItems[0]
                            ->schedule
                            ->activity
                            ->id,
                        'date' => $booking
                            ->bookingItems[0]
                            ->schedule
                            ->date,
                        'staff_id' => 0 // TODO: get staff id

                    ];
                    return response()
                        ->json(
                            [
                                'data' => $data
                            ]
                        );
                } else {
                    return response()
                        ->json(
                            [
                                'message' => 'You can not change booking date after ' . $booking->bookingItems[0]->schedule->change_lead_time . ' hours'
                            ],
                            422);
                }
            }

        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function getYourDetails($tenant, $reference)
    {
        try {
            $tenantData = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $booking = Booking::where('reference',$reference)->first();
            $data = [
                'schedule_id' => $booking->bookingItems[0]->schedule->id,
                'activity_id' => $booking->bookingItems[0]->schedule->activity->id,
                'date' => $booking->bookingItems[0]->schedule->date,
                'staff_id' => 0 // TODO: get staff id
            ];
            return response()->json(['data' => $data], 200);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function changeBookingDate($tenant, $reference, Request $request)
    {
        try {
            $tenantData = Tenant::find($tenant);
            tenancy()->initialize($tenant);
            $booking = Booking::where('reference', $reference)->first();
            $oldDate = $booking->bookingItems[0]->schedule->date;
            $schedule = '';
            if ($booking) {

                $booking->bookingItems[0]->schedule->update(
                    ['booked_slots' => $booking->bookingItems[0]->schedule->booked_slots - $booking->participants]
                );
                $booking->bookingItems[0]->update(
                    ['item_id' => $request->selectedScheduleId]
                );

                $schedule = Schedule::findOrFail($request->selectedScheduleId);
                $schedule->update(
                    ['booked_slots' => $booking->participants + $schedule->booked_slots]
                );
//                $booking->customer->notify(new BookingDateChangeNotification($reference, $oldDate, $schedule->date));
            }

            return response()->json(['message' => 'Date successfully updated'], 200);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function updateYourDetails(YourDetailUpdateRequest $request ,$tenant, $reference): JsonResponse
    {
//        try {
            $tenantData = Tenant::find($tenant);
            tenancy()->initialize($tenant);
        $booking = Booking::where('reference', $reference)->first();
        if ($booking) {
            $details = json_decode($request->details, true);
            $index = 1;
            $booking->customer->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
            ]);
            foreach ($booking->booking_participants as $participant) {
                if (isset($details['user' . $index])) {
                    $userData = $details['user' . $index];

                    // Update participant details and other general information
                    $participant->update([
                        'first_name' => $userData['first_name'],
                        'last_name' => $userData['last_name'],
                        'details' => json_encode($userData),
                    ]);

                    $index++;
                }
            }
//            $booking->customer->notify(new BookingDetalsChangeNotification($reference));
            return response()->json([
                'status' => true,
                'message' => 'Booking participants data updated!',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found!',
            ], 404);
        }

//        } catch (Exception $exception) {
//            Log::error($exception->getMessage());
//            return response()->json(['message' => 'Something went wrong'], 500);
//        }
    }
}
