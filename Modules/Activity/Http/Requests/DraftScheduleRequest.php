<?php

namespace Modules\Activity\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $assigned_staff
 */
class DraftScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i',
            'allocated_slots' => 'numeric',
            'min_number_of_places' => 'numeric',
            'max_number_of_places' => 'numeric',
            'price_tier_id' => 'exists:price_tiers,id',
            'price'=>'nullable|numeric',
            'location_id'=>'nullable|numeric',
            'assigned_staff'=> 'array',
            'is_published'=>'boolean',
            'change_lead_time' => 'nullable|numeric',
            'cancel_lead_time' => 'nullable|numeric',
        ];
    }
}
