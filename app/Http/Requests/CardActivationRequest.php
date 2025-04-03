<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CardActivationRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a kérés végrehajtására.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A kérésre vonatkozó validációs szabályok.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'card_code' => 'required|string|max:50',
            'profile_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * A validációs hibaüzenetek testreszabása.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'card_code.required' => 'A kártya kód megadása kötelező.',
            'card_code.string' => 'A kártya kód csak szöveg lehet.',
            'card_code.max' => 'A kártya kód maximum 50 karakter lehet.',
            'profile_name.string' => 'A profil név csak szöveg lehet.',
            'profile_name.max' => 'A profil név maximum 255 karakter lehet.',
        ];
    }
}
