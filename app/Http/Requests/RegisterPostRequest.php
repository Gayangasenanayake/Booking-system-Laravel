<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'password' => 'required|string|confirmed|min:8',
            'email' => 'required|string|email|max:255|unique:users',
            'tenant_name' => 'required|string|max:255|unique:tenants,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->all();

        throw new HttpResponseException(
            response()->json([
                'message' => implode(', ', $errors),
                'errors' => [
                    'name' => $errors['name'][0] ?? false,
                    'password' => $errors['password'][0] ?? false,
                    'email' => $errors['email'][0] ?? false,
                    'tenant_name' => $errors['tenant_name'][0] ?? false,
                ],
            ], 422)
        );
    }
}
