<?php

namespace App\Traits;

use App\Models\Tenant;
use Exception;
use Modules\Booking\Entities\Booking;
use Modules\Course\Entities\Course;
use Modules\Product\Entities\Product;
use Modules\Schedule\Entities\Schedule;

trait BookingDetailsTrait
{
    public function getBookingDetails($tenant, $reference)
    {
        try {
            $tenant = Tenant::find($tenant);
            tenancy()->initialize($tenant);



            $bookedData = Booking::where('reference', $reference)
                ->with('bookingItems')
                ->first();




            $formattedData = [
                'id' => $bookedData->id,
                'customer_id' => $bookedData->customer_id,
                'date' => $bookedData->date,
                'time' => $bookedData->time,
                'reference' => $bookedData->reference,
                'tenant' => $tenant->id,
                'booking_items' => [],
                'schedule_data' => [],
                'activity_data' => [],
            ];

            foreach ($bookedData->bookingItems as $item) {
                switch ($item->item_type) {
                    case 'schedule':
                        $scheduleData = Schedule::with('activity')->find($item->item_id);
                        $formattedData['schedule_data'][] = $scheduleData;
                        $formattedData['item_price'] = $bookedData->sub_total / $bookedData->participants;
                        $formattedData['activity_data'][] = $scheduleData?->activity;

                        $item_price=$this->getActivityPriceDetails($tenant->id,$item->item_id,$scheduleData?->activity->id,$bookedData->date,$bookedData->participants);
                        $processingFee = $bookedData->sub_total * (ENV('BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE')/ 100);
                        $formattedData['booking_items'][] = [
                            'item_type' => $item->item_type,
                            'item_id' => $item->item_id,
                            'title' => $scheduleData->activity->title,
                            'item_price' => $item_price['base_price'],
                            'schedule_date' => $scheduleData->date,
                            'schedule_start_time' => $scheduleData->start_time,
                            'schedule_end_time' => $scheduleData->end_time,
                            'slots'=> $item->number_of_slots,
                            'quantity'=>null,
                            'images'=>$scheduleData->activity->images,
                            'paid' => $bookedData->paid,
                            'sub_total' => $bookedData->sub_total,
                            'total' => $bookedData->total,
                            'tax' => $bookedData->tax,
                            'processing_fee' => $processingFee
                            // You can exclude 'item_data' for schedules if needed as it's available in 'schedule_data'
                        ];
                        break;
                    case 'course':
                        $courseData = Course::find($item->item_id);
                        $formattedData['booking_items'][] = [
                            'item_type' => $item->item_type,
                            'item_id' => $item->item_id,
                            'item_data' => $courseData,
                        ];
                        break;
                    case 'product':
                        $productData = Product::find($item->item_id);
                        $formattedData['booking_items'][] = [
                            'item_type' => $item->item_type,
                            'item_id' => $item->item_id,
                            'title'=> $productData->title,
                            'item_price'=> $productData->productPricingInfo->base_price,
                            'schedule_date' => null,
                            'schedule_start_time' => null,
                            'schedule_end_time' => null,
                            'slots'=> null,
                            'quantity'=>$item->quantity,
                            'images'=>$productData->images,
                            'paid' => 0,
                            'sub_total' => 0,
                            'total' => 0,
                            'tax' => 0,
                            'processing_fee' => 0,
                            'item_data' => $productData,
                        ];
                        break;
                    default:
                        break;
                }
            }
            return $formattedData;
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}
