<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $main_image
 * @property mixed $thumbnail_image
 */
class ProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string',
            'sku' => 'required|string',
            'brief_description' => 'required|string',
            'long_description' => 'nullable|string',
            'is_deleted',
            'tags'=> 'nullable',
            'main_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'thumbnail_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
