<?php

namespace Modules\Activity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityPriceTierRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name'=> 'required|string',
            'price'=> 'required|numeric',
            'advertised_price'=> 'required|numeric',
            'effective_from_date' => 'nullable|date',
            'effective_to_date' => 'nullable|date|after:effective_from_date',
            'minimum_number_of_participants'=>'nullable|integer|min:0',
            'maximum_number_of_participants'=>'nullable|integer|min:0',
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
