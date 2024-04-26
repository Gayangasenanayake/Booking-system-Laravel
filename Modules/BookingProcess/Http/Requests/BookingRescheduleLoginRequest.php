<?php

namespace Modules\BookingProcess\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;

class BookingRescheduleLoginRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reference' => 'required',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */

    protected function failedValidation(ValidationValidator|Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => Arr::flatten($validator->getMessageBag()->all()),
        ], 422));
    }
    public function authorize(): bool
    {
        return true;
    }


}
