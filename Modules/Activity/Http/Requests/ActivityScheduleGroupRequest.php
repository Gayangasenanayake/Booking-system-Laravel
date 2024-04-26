<?php

namespace Modules\Activity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $day
 * @property mixed $assigned_staff
 * @property mixed $days
 * @property mixed $location_id
 */
class ActivityScheduleGroupRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'from_date'=> 'required|date|after:today',
            'to_date'=> 'required|date|after:today',
            'day'=> 'required',
            'start_time'=> 'required|date_format:H:i',
            'end_time'=> 'required|date_format:H:i',
            'allocated_slots'=> 'required|numeric|min:1',
            'min_number_of_places'=> 'required|numeric|min:1',
            'max_number_of_places'=> 'nullable|numeric|min:1',
            'price_tier_id'=> 'nullable',
            'assigned_staff'=> 'array',
            'price'=>'nullable|numeric',
            'location_id'=>'numeric|nullable',
            'change_lead_time' => 'nullable|numeric',
            'cancel_lead_time' => 'nullable|numeric',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }
}
