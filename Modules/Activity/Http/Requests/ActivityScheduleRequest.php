<?php

namespace Modules\Activity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $assigned_staff
 */
class ActivityScheduleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'date'=> 'required|date|after:today',
            'start_time' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
                        $fail('The '.$attribute.' field must match the format H:i or H:i:s.');
                    }
                },
            ],
            'end_time' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
                        $fail('The '.$attribute.' field must match the format H:i or H:i:s.');
                    }
                },
            ],
            'allocated_slots'=> 'required|numeric|min:1',
            'min_number_of_places'=> 'required|numeric|min:1',
            'max_number_of_places'=> 'nullable|numeric|min:1',
            'price_tier_id'=> 'nullable|exists:price_tiers,id',
            'price'=>'nullable|numeric',
            'location_id'=>'nullable|numeric',
            'assigned_staff'=> 'array',
            'is_published'=>'boolean',
            'change_lead_time' => 'nullable|numeric',
            'cancel_lead_time' => 'nullable|numeric',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
