<?php

namespace Modules\Widget\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WidgetStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'activities.*' => 'required|exists:activities,id',
            'widget_type' => 'required|in:grid-list,sidebar-course-widget,half-box-view,list-view',
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
