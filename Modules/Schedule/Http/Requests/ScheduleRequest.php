<?php

namespace Modules\Schedule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date'=> 'required|date|after:today',
            'start_time'=> 'required|date_format:H:i',
            'end_time'=> 'required|date_format:H:i',
            'allocated_slots'=> 'required|integer|min:1',
            'min_number_of_places'=> 'required|integer|min:1',
            'max_number_of_places'=> 'required|integer|min:1',
            'activity_id'=> 'required|integer',
            'price'=> 'nullable|numeric',
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
