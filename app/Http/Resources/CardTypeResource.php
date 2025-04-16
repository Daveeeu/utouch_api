<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardTypeResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'valid_days' => $this->valid_days,
            'price' => $this->price,
            'features' => $this->features,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Opcionális meta információk és kapcsolatok
            'cards_count' => $this->when($request->routeIs('admin.card-types.*'), function () {
                return $this->cards()->count();
            }),

            // Ha a kártyákat is betöltöttük, akkor adjuk vissza őket
            'cards' => CardResource::collection($this->whenLoaded('cards')),
        ];
    }
}
