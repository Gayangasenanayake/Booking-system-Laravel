<?php

namespace Modules\Activity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class ActivityMainInfoRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'activity_code' => 'required|string|max:255',
            'qty_label' => 'required|string|max:255',
            'brief_description' => 'required|string',
            'qty_label_plural' => 'nullable|string|max:255',
            'is_selecting_staff' => 'nullable',
            'long_description' => 'nullable|string',
            'tags' => 'nullable',
            'main_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'thumbnail_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'images' => 'nullable',
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
