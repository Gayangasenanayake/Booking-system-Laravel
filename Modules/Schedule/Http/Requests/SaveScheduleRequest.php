<?php

namespace Modules\Schedule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveScheduleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i',
            'allocated_slots' => 'integer',
            'min_number_of_places' => 'integer',
            'max_number_of_places' => 'integer',
            'activity_id' => 'integer',
            'price_tier_id' => 'integer',
            'is_published'
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
