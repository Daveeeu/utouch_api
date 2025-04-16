<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create cards');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'card_type_id' => 'required|exists:card_types,id',
            'code' => 'nullable|string|max:255|unique:cards,code',
            'status' => 'nullable|string|in:inactive,active,expired',
            'user_id' => 'nullable|exists:users,id',
            'profile_id' => 'nullable|exists:profiles,id',
            'notes' => 'nullable|string',
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
            'card_type_id.required' => 'A kártyatípus kiválasztása kötelező.',
            'card_type_id.exists' => 'A kiválasztott kártyatípus nem létezik.',
            'code.unique' => 'Ez a kártya kód már használatban van.',
            'status.in' => 'A státusz csak inactive, active vagy expired lehet.',
            'user_id.exists' => 'A kiválasztott felhasználó nem létezik.',
            'profile_id.exists' => 'A kiválasztott profil nem létezik.',
        ];
    }
}
