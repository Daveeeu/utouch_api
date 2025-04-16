<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCardTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage card types');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:card_types,name',
            'description' => 'nullable|string',
            'valid_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
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
            'name.required' => 'A kártyatípus neve kötelező.',
            'name.unique' => 'Ez a kártyatípus név már létezik.',
            'valid_days.required' => 'Az érvényesség időtartama kötelező.',
            'valid_days.integer' => 'Az érvényesség időtartama egész szám kell legyen.',
            'valid_days.min' => 'Az érvényesség időtartama legalább 1 nap kell legyen.',
            'price.required' => 'Az ár megadása kötelező.',
            'price.numeric' => 'Az ár szám kell legyen.',
            'price.min' => 'Az ár nem lehet negatív.',
        ];
    }
}
