<?php

namespace App\Traits;

use App\Http\Resources\DataResource;
use App\Models\Tenant;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Schedule\Entities\Schedule;

trait CommonFunctionTrait
{
    public function getActivityPriceDetails ($tenant, $scheduleId, $activityId, $month, $slotCount)
    {
        $tenant = Tenant::find($tenant);
        tenancy()->initialize($tenant);
//        try {
        $result = explode('-', $month);
        $year = $result[0];
        $month = $result[1];
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $schedule = Schedule::where('id', $scheduleId)
            ->first();
        $scheduleDate=$schedule->date;
        if ($schedule->price) {
            $data = [
                'tax_percentage' => (int)ENV('TAX_PERCENTAGE'),
                'service_charge_percentage' => (int)ENV('BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'),
                'base_price' => $schedule->price,
                'advertised_price' => $schedule->price,
                'sub_total' => $schedule->price * $slotCount,
                'tax' => (ENV('TAX_PERCENTAGE') / 100) * ($schedule->price * $slotCount),
                'service_charge' => (ENV(
                            'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                        ) / 100) * ($schedule->price * $slotCount),
                'final_total' => ($schedule->price * $slotCount) + ((ENV(
                                'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                            ) / 100) * ($schedule->price * $slotCount)) + ((ENV(
                                'TAX_PERCENTAGE'
                            ) / 100) * ($schedule->price * $slotCount))
            ];
        }
        else if(!empty($schedule->activity->priceTiers->toArray())){
            $filteredDatePriceTiers = array_values(array_filter($schedule->activity->priceTiers->toArray(), function ($item) use ($scheduleDate) {
                if ($item["effective_from_date"] === null && $item["effective_to_date"] === null) {
                    return false;
                }
                $fromDate = $item["effective_from_date"] ? strtotime($item["effective_from_date"]) : PHP_INT_MIN;
                $toDate = $item["effective_to_date"] ? strtotime($item["effective_to_date"]) : PHP_INT_MAX;
                $givenDateTimestamp = strtotime($scheduleDate);
                return $givenDateTimestamp >= $fromDate && $givenDateTimestamp <= $toDate;
            }));
            if(!empty($filteredDatePriceTiers)){
                $filteredDateQuantityPriceTiers = array_values(array_filter($filteredDatePriceTiers, function ($entry) use ($slotCount) {
                    return $slotCount >= $entry["minimum_number_of_participants"] && $slotCount <= $entry["maximum_number_of_participants"];
                }));
                if (!empty($filteredDateQuantityPriceTiers)){
                    $data = [
                        'tax_percentage' => (int)ENV('TAX_PERCENTAGE'),
                        'service_charge_percentage' => (int)ENV('BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'),
                        'base_price' => $filteredDateQuantityPriceTiers[0]['price'],
                        'advertised_price' => $filteredDateQuantityPriceTiers[0]['advertised_price'] ,
                        'sub_total' => $filteredDateQuantityPriceTiers[0]['price']* $slotCount,
                        'tax' => (ENV(
                                    'TAX_PERCENTAGE'
                                ) / 100) * ($filteredDateQuantityPriceTiers[0]['price'] * $slotCount),
                        'service_charge' => (ENV(
                                    'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                ) / 100) * ($filteredDateQuantityPriceTiers[0]['price'] * $slotCount),
                        'final_total' => ($filteredDateQuantityPriceTiers[0]['price'] * $slotCount) + ((ENV(
                                        'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                    ) / 100) * ($filteredDateQuantityPriceTiers[0]['price'] * $slotCount)) + ((ENV(
                                        'TAX_PERCENTAGE'
                                    ) / 100) * ($filteredDateQuantityPriceTiers[0]['price'] * $slotCount))
                    ];
                }else{
                    // if we have multiple price tiers between the same date range how we decide the price for the schedule
                    $filteredDateWithoutQuantityPriceTiers = array_values(array_filter($filteredDatePriceTiers, function ($entry) use ($slotCount) {
                        return !$slotCount >= $entry["minimum_number_of_participants"] && !$slotCount <= $entry["maximum_number_of_participants"];
                    }));
                    //when there are no price tires for selected date without given quantity sending base price
                    if (!empty($filteredDateWithoutQuantityPriceTiers)) {
                        $data = [
                            'tax_percentage' => (int)ENV('TAX_PERCENTAGE'),
                            'service_charge_percentage' => (int)ENV('BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'),
                            'base_price' => $filteredDateWithoutQuantityPriceTiers[0]['price'],
                            'advertised_price' => $filteredDateWithoutQuantityPriceTiers[0]['advertised_price'] ,
                            'sub_total' => $filteredDateWithoutQuantityPriceTiers[0]['price']* $slotCount,
                            'tax' => (ENV(
                                        'TAX_PERCENTAGE'
                                    ) / 100) * ($filteredDateWithoutQuantityPriceTiers[0]['price'] * $slotCount),
                            'service_charge' => (ENV(
                                        'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                    ) / 100) * ($filteredDateWithoutQuantityPriceTiers[0]['price'] * $slotCount),
                            'final_total' => ($filteredDateWithoutQuantityPriceTiers[0]['price'] * $slotCount) + ((ENV(
                                            'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                        ) / 100) * ($filteredDateWithoutQuantityPriceTiers[0]['price'] * $slotCount)) + ((ENV(
                                            'TAX_PERCENTAGE'
                                        ) / 100) * ($filteredDateWithoutQuantityPriceTiers[0]['price'] * $slotCount))
                        ];
                    } else {
                        $data = [
                            'tax_percentage' => (int)ENV('TAX_PERCENTAGE'),
                            'service_charge_percentage' => (int)ENV('BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'),
                            'base_price' => $schedule->activity->pricingInfo->base_price,
                            'advertised_price' => $schedule->activity->pricingInfo->advertised_price,
                            'sub_total' => $schedule->activity->pricingInfo->base_price * $slotCount,
                            'tax' => (ENV(
                                        'TAX_PERCENTAGE'
                                    ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount),
                            'service_charge' => (ENV(
                                        'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                    ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount),
                            'final_total' => ($schedule->activity->pricingInfo->base_price * $slotCount) + ((ENV(
                                            'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                        ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount)) + ((ENV(
                                            'TAX_PERCENTAGE'
                                        ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount))
                        ];
                    }


                }
            }
            else{
                //todo: if quantity price tiers have multiples we need to validate them when create
                $filteredQuantityPriceTiers = array_values(array_filter($schedule->activity->priceTiers->toArray(), function ($entry) use ($slotCount) {
                    return $slotCount >= $entry["minimum_number_of_participants"] && $slotCount <= $entry["maximum_number_of_participants"];
                }));
                if (!empty($filteredQuantityPriceTiers)){
                    $data = [
                        'tax_percentage' => (int)ENV('TAX_PERCENTAGE'),
                        'service_charge_percentage' => (int)ENV('BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'),
                        'base_price' => $filteredQuantityPriceTiers[0]['price'],
                        'advertised_price' => $filteredQuantityPriceTiers[0]['advertised_price'] ,
                        'sub_total' => $filteredQuantityPriceTiers[0]['price']* $slotCount,
                        'tax' => (ENV(
                                    'TAX_PERCENTAGE'
                                ) / 100) * ($filteredQuantityPriceTiers[0]['price'] * $slotCount),
                        'service_charge' => (ENV(
                                    'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                ) / 100) * ($filteredQuantityPriceTiers[0]['price'] * $slotCount),
                        'final_total' => ($filteredQuantityPriceTiers[0]['price'] * $slotCount) + ((ENV(
                                        'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                    ) / 100) * ($filteredQuantityPriceTiers[0]['price'] * $slotCount)) + ((ENV(
                                        'TAX_PERCENTAGE'
                                    ) / 100) * ($filteredQuantityPriceTiers[0]['price'] * $slotCount))
                    ];
                }
                else{
                    $data = [
                        'tax_percentage' => (int)ENV('TAX_PERCENTAGE'),
                        'service_charge_percentage' => (int)ENV('BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'),
                        'base_price' => $schedule->activity->pricingInfo->base_price,
                        'advertised_price' => $schedule->activity->pricingInfo->advertised_price,
                        'sub_total' => $schedule->activity->pricingInfo->base_price * $slotCount,
                        'tax' => (ENV(
                                    'TAX_PERCENTAGE'
                                ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount),
                        'service_charge' => (ENV(
                                    'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount),
                        'final_total' => ($schedule->activity->pricingInfo->base_price * $slotCount) + ((ENV(
                                        'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                                    ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount)) + ((ENV(
                                        'TAX_PERCENTAGE'
                                    ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount))
                    ];
                }
            }
        }
        else{
            $data = [
                'tax_percentage' => (int)ENV('TAX_PERCENTAGE'),
                'service_charge_percentage' => (int)ENV('BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'),
                'base_price' => $schedule->activity->pricingInfo->base_price,
                'advertised_price' => $schedule->activity->pricingInfo->advertised_price,
                'sub_total' => $schedule->activity->pricingInfo->base_price * $slotCount,
                'tax' => (ENV(
                            'TAX_PERCENTAGE'
                        ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount),
                'service_charge' => (ENV(
                            'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                        ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount),
                'final_total' => ($schedule->activity->pricingInfo->base_price * $slotCount) + ((ENV(
                                'BETTER_BOOKING_PROCESSING_FEE_PERCENTAGE'
                            ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount)) + ((ENV(
                                'TAX_PERCENTAGE'
                            ) / 100) * ($schedule->activity->pricingInfo->base_price * $slotCount))
            ];
        }
        return new DataResource($data);
//        } catch (Exception $e) {
//            Log::error($e->getMessage());
//            return response()->json(['message' => 'Something went wrong'], 500);
//        }
    }
}
