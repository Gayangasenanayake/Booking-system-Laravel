<?php

namespace Modules\BookingProcess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_data' => 'required|array',
            'booking_data' => 'required|array',
            'booking_items' => 'required|array',
            'booking_participants' => 'array',
            'booking_by_myself',
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
