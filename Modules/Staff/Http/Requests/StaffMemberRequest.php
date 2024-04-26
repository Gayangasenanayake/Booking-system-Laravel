<?php

namespace Modules\Staff\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $avatar
 */
class StaffMemberRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'=> 'required|string',
            'title'=> 'required|string',
            'experience'=> 'string|nullable',
            'profile_data'=> 'string|nullable',
            'status'=> 'string|nullable',
            'email'=> 'required|email',
            'avatar'=> 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'images' => 'nullable',
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
