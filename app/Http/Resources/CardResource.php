<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'profile_id' => $this->profile_id,
            'card_type_id' => $this->card_type_id,
            'activated_at' => $this->activated_at,
            'expires_at' => $this->expires_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Kapcsolatok
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'email' => $this->user->email,
                ];
            }),

            'profile' => $this->whenLoaded('profile', function () {
                return [
                    'id' => $this->profile->id,
                    'name' => $this->profile->name,
                    'type' => $this->profile->type,
                    'is_public' => $this->profile->is_public,
                ];
            }),

            'card_type' => $this->whenLoaded('cardType', function () {
                return [
                    'id' => $this->cardType->id,
                    'name' => $this->cardType->name,
                    'valid_days' => $this->cardType->valid_days,
                    'price' => $this->cardType->price,
                ];
            }),
        ];
    }
}
