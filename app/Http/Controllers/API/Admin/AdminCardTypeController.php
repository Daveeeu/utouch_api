<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCardTypeRequest;
use App\Http\Requests\UpdateCardTypeRequest;
use App\Http\Resources\CardTypeResource;
use App\Models\CardType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminCardTypeController extends Controller
{
    /**
     * Konstruktor - jogosultságok ellenőrzése
     */
    public function __construct()
    {
        $this->middleware('permission:manage card types');
    }

    /**
     * Kártyatípusok listázása
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CardType::query();

        // Szűrés név szerint
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return CardTypeResource::collection($query->paginate($request->per_page ?? 15));
    }

    /**
     * Új kártyatípus létrehozása
     */
    public function store(StoreCardTypeRequest $request): CardTypeResource
    {
        $cardType = CardType::create($request->validated());

        return new CardTypeResource($cardType);
    }

    /**
     * Kártyatípus részletes adatai
     */
    public function show(CardType $cardType): CardTypeResource
    {
        return new CardTypeResource($cardType);
    }

    /**
     * Kártyatípus adatainak frissítése
     */
    public function update(UpdateCardTypeRequest $request, CardType $cardType): CardTypeResource
    {
        $cardType->update($request->validated());

        return new CardTypeResource($cardType);
    }

    /**
     * Kártyatípus törlése
     */
    public function destroy(CardType $cardType): JsonResponse
    {
        // Ellenőrizzük, van-e hozzá kapcsolódó kártya
        if ($cardType->cards()->exists()) {
            return response()->json([
                'message' => 'A kártyatípus nem törölhető, mert már vannak hozzá tartozó kártyák.'
            ], 422);
        }

        $cardType->delete();

        return response()->json(['message' => 'A kártyatípus sikeresen törölve.']);
    }
}
