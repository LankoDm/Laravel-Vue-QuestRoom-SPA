<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => 'sometimes|required|in:admin,manager,user',
            'name' => 'sometimes|required|string|max:255',
            'phone' => ['nullable', 'string', 'regex:/^\+380 \(\d{2}\) \d{3}-\d{2}-\d{2}$/'],
        ];
    }
    public function messages(): array
    {
        return [
            'role.in' => 'Обрано неіснуючу роль.',
            'name.required' => 'Ім\'я користувача обов\'язкове.',
            'phone.regex' => 'Номер телефону введено не повністю.',
        ];
    }
}
