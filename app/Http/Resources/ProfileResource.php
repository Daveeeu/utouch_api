<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'image' => $this->image,
            'contact_info' => $this->contact_info,
            'social_links' => $this->social_links,
            'meta_data' => $this->meta_data,
            'visits' => $this->visits,
            'is_public' => $this->is_public,
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

            // Ha a kártyákat is betöltöttük, akkor adjuk vissza őket
            'cards' => CardResource::collection($this->whenLoaded('cards')),
        ];
    }
}
