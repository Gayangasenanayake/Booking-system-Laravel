<?php

namespace Modules\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name'=> 'required',
            'frequency'=> 'required',
            'price'=> 'required|numeric',
            'original_price'=> 'required|numeric',
            'summery'=> 'string|nullable',
            'long_description'=> 'string|nullable',
            'activity_id'=> 'required|numeric',
            'sessions' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'activity_id.required' => 'The activity field is required.',
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
