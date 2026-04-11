<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ім\'я є обов\'язковим.',
            'name.max' => 'Ім\'я не може перевищувати 255 символів.',
            'email.required' => 'Email є обов\'язковим.',
            'email.email' => 'Введіть коректну адресу електронної пошти.',
            'email.unique' => 'Користувач з таким email вже зареєстрований.',
            'password.required' => 'Пароль є обов\'язковим.',
            'password.min' => 'Пароль має містити щонайменше 8 символів.',
            'password.confirmed' => 'Паролі не співпадають.',
        ];
    }
}
