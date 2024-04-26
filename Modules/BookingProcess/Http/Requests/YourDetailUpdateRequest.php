<?php

namespace Modules\BookingProcess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class YourDetailUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'string',
            'email' => 'email',
            'mobile' => 'string',
            'details' => 'string'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
