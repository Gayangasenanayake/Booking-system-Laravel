<?php

namespace Modules\Booking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParticipantRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name'=>'required|string',
            'last_name'=>'string',
            'email'=>'email',
            'age'=>'integer',
            'dietary_requirements'=>'string',
            'weight'=>'integer',
            'height'=>'integer',
            'health_issues'=>'string',
            'other'=>'string',
            'details'=>'string'
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
