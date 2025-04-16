<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit cards');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'card_type_id' => 'sometimes|exists:card_types,id',
            'code' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('cards')->ignore($this->route('card')),
            ],
            'status' => 'sometimes|string|in:inactive,active,expired',
            'user_id' => 'sometimes|nullable|exists:users,id',
            'profile_id' => 'sometimes|nullable|exists:profiles,id',
            'activated_at' => 'sometimes|nullable|date',
            'expires_at' => 'sometimes|nullable|date',
            'notes' => 'sometimes|nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'card_type_id.exists' => 'A kiválasztott kártyatípus nem létezik.',
            'code.unique' => 'Ez a kártya kód már használatban van.',
            'status.in' => 'A státusz csak inactive, active vagy expired lehet.',
            'user_id.exists' => 'A kiválasztott felhasználó nem létezik.',
            'profile_id.exists' => 'A kiválasztott profil nem létezik.',
            'activated_at.date' => 'Az aktiválás időpontja érvényes dátum kell legyen.',
            'expires_at.date' => 'A lejárat időpontja érvényes dátum kell legyen.',
        ];
    }
}
