<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
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
        $room = $this->route('room');
        $roomId = is_object($room) ? $room->id : $room;

        return [
            'name' => [
                'required',
                'min:5',
                'max:35',
                Rule::unique('rooms', 'name')->ignore($roomId)
            ],
            'description' => 'required|min:20',
            'difficulty' => 'required|in:easy,medium,hard,ultra hard',
            'age' => 'required|string',
            'hint_phrase' => 'nullable|string|max:255',
            'genre' => 'nullable|string|max:50',
            'image_path' => [
                $roomId ? 'nullable' : 'required',
                'array',
                'max:5'
            ],
            'image_path.*' => [
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:4096'
            ],
            'min_players' => 'required|integer|min:1',
            'max_players' => 'required|integer|gte:min_players',
            'weekday_price' => 'required|integer|min:0',
            'weekend_price' => 'required|integer|min:0',
            'duration_minutes' => 'required|integer|min:10',
            'is_active' => 'required|boolean',
            'slug' => [
                'required',
                Rule::unique('rooms', 'slug')->ignore($roomId)
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Введіть назву кімнати.',
            'name.min' => 'Назва має містити щонайменше 5 символів.',
            'name.max' => 'Назва занадто довга (максимум 35 символів).',
            'name.unique' => 'Кімната з такою назвою вже існує.',
            'description.required' => 'Додайте опис кімнати.',
            'description.min' => 'Опис має бути більш детальним (мінімум 20 символів).',
            'difficulty.required' => 'Оберіть рівень складності.',
            'age.required' => 'Вкажіть вікове обмеження.',
            'image_path.required' => 'Завантажте хоча б одне зображення для обкладинки.',
            'image_path.max' => 'Можна завантажити не більше 5 зображень.',
            'image_path.*.image' => 'Один із файлів не є зображенням.',
            'image_path.*.mimes' => 'Формат зображення має бути: jpeg, png, jpg або webp.',
            'image_path.*.max' => 'Розмір одного зображення не повинен перевищувати 4 МБ.',
            'min_players.required' => 'Вкажіть мінімальну кількість гравців.',
            'max_players.required' => 'Вкажіть максимальну кількість гравців.',
            'max_players.gte' => 'Максимальна кількість гравців не може бути меншою за мінімальну.',
            'weekday_price.required' => 'Вкажіть ціну для будніх днів.',
            'weekend_price.required' => 'Вкажіть ціну для вихідних днів.',
            'duration_minutes.required' => 'Вкажіть тривалість гри.',
            'slug.required' => 'Унікальний URL-ідентифікатор (slug) обов\'язковий.',
            'slug.unique' => 'Цей URL-ідентифікатор вже зайнятий.',
        ];
    }
}
