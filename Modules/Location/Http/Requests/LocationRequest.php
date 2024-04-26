<?php

namespace Modules\Location\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'is_enabled'=>'boolean',
            'name'=>'required|string',
            'address_line_1'=>'required|string',
            'address_line_2'=>'nullable|string',
            'city'=>'required|string',
            'post_code'=>'required|numeric',
            'instructions'=>'nullable|string',
            'notes'=>'nullable|string',
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
