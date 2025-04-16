<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Alap felhasználói adatok
        $data = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Csak admin felhasználók számára vagy a saját adatoknál adjuk vissza a jogosultságokat
        if ($request->user() && ($request->user()->isAdmin() || $request->user()->id === $this->id)) {
            $data['roles'] = $this->roles->pluck('name');
            $data['permissions'] = $this->getAllPermissions()->pluck('name');
        }

        // Kapcsolatok
        $data['profiles'] = ProfileResource::collection($this->whenLoaded('profiles'));
        $data['cards'] = CardResource::collection($this->whenLoaded('cards'));

        return $data;
    }
}
