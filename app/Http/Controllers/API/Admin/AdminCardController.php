<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\CardType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AdminCardController extends Controller
{
    /**
     * Konstruktor - jogosultságok ellenőrzése
     */
    public function __construct()
    {
        $this->middleware('permission:view cards', ['only' => ['index', 'show']]);
        $this->middleware('permission:create cards', ['only' => ['store']]);
        $this->middleware('permission:edit cards', ['only' => ['update']]);
        $this->middleware('permission:delete cards', ['only' => ['destroy']]);
        $this->middleware('permission:assign cards', ['only' => ['assignToUser']]);
    }

    /**
     * Kártyák listázása
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Card::with(['user', 'profile', 'cardType'])
            ->orderBy('created_at', 'desc');

        // Szűrés státusz szerint
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Szűrés kártyatípus szerint
        if ($request->has('card_type_id')) {
            $query->where('card_type_id', $request->card_type_id);
        }

        // Szűrés felhasználó szerint
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Szűrés kód szerint
        if ($request->has('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        return CardResource::collection($query->paginate($request->per_page ?? 15));
    }

    /**
     * Új kártya létrehozása
     */
    public function store(StoreCardRequest $request): CardResource
    {
        $data = $request->validated();

        // Ha nincs megadva kód, generálunk egyet
        if (!isset($data['code'])) {
            $data['code'] = Card::generateUniqueCode();
        }

        // Státusz beállítása
        if (!isset($data['status'])) {
            $data['status'] = Card::STATUS_INACTIVE;
        }

        $card = Card::create($data);

        // Ha van user_id és státusz aktív, akkor aktiváljuk
        if (isset($data['user_id']) && $data['status'] === Card::STATUS_ACTIVE) {
            $card->activate();
        }

        return new CardResource($card->load(['user', 'profile']));
    }

    /**
     * Kártya részletes adatai
     */
    public function show(Card $card): CardResource
    {
        return new CardResource($card->load(['user', 'profile', 'cardType']));
    }

    /**
     * Kártya adatainak frissítése
     */
    public function update(UpdateCardRequest $request, Card $card): CardResource
    {
        $data = $request->validated();

        // Ha változik a státusz aktívra, akkor aktiváljuk
        if (isset($data['status']) && $data['status'] === Card::STATUS_ACTIVE && $card->status !== Card::STATUS_ACTIVE) {
            $card->activate();

            // Töröljük a státusz-t, mert már beállítottuk
            unset($data['status']);
        }

        $card->update($data);

        return new CardResource($card->load(['user', 'profile', 'cardType']));
    }

    /**
     * Kártya törlése
     */
    public function destroy(Card $card): JsonResponse
    {
        $card->delete();

        return response()->json(['message' => 'A kártya sikeresen törölve.']);
    }

    /**
     * Kártya hozzárendelése felhasználóhoz
     */
    public function assignToUser(Request $request, Card $card): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        if ($card->isAssigned()) {
            return response()->json(['message' => 'A kártya már hozzá van rendelve egy felhasználóhoz.'], 422);
        }

        $card->update([
            'user_id' => $request->user_id
        ]);

        return response()->json([
            'message' => 'A kártya sikeresen hozzárendelve a felhasználóhoz.',
            'card' => new CardResource($card->load(['user', 'profile', 'cardType']))
        ]);
    }

    /**
     * Kártya aktiválása
     */
    public function activate(Card $card): JsonResponse
    {
        if ($card->isActive()) {
            return response()->json(['message' => 'A kártya már aktív.'], 422);
        }

        $card->activate();

        return response()->json([
            'message' => 'A kártya sikeresen aktiválva.',
            'card' => new CardResource($card->load(['user', 'profile', 'cardType']))
        ]);
    }

    /**
     * Kártya típusok lekérdezése (select mezőkhöz)
     */
    public function cardTypes()
    {
        return CardType::select('id', 'name', 'valid_days', 'price')->get();
    }

    /**
     * Felhasználók lekérdezése (select mezőkhöz)
     */
    public function users()
    {
        return User::select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"), 'email')->get();
    }
}
