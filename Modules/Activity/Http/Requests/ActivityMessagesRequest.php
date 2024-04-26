<?php

namespace Modules\Activity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityMessagesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'subject' => 'required|string',
            'body' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'reply_email' => 'nullable|email',
            'from' => 'required',
            'to' => 'required',
            'days' => 'required|integer',
            'after_or_before'=>'required|string'
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
