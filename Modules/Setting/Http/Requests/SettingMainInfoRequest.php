<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $logo
 */
class SettingMainInfoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'business_name' => 'required|string|max:255',
            'number' => 'required|regex:/^[0-9+\s]+$/',
            'email' => 'required|email',
            'business_registration' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'logo' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048'
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
