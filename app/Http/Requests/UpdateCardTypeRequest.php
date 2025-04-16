<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCardTypeRequest extends FormRequest
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
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('card_types')->ignore($this->route('cardType')),
            ],
            'description' => 'sometimes|nullable|string',
            'valid_days' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'features' => 'sometimes|nullable|array',
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
            'name.unique' => 'Ez a kártyatípus név már létezik.',
            'valid_days.integer' => 'Az érvényesség időtartama egész szám kell legyen.',
            'valid_days.min' => 'Az érvényesség időtartama legalább 1 nap kell legyen.',
            'price.numeric' => 'Az ár szám kell legyen.',
            'price.min' => 'Az ár nem lehet negatív.',
        ];
    }
}
