<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class CardController extends Controller
{
    /**
     * A kártya service példánya.
     *
     * @var CardService
     */
    protected $cardService;

    /**
     * Új kontroller példány létrehozása.
     *
     * @param CardService $cardService
     */
    public function __construct(CardService $cardService)
    {
        $this->cardService = $cardService;
    }

    /**
     * Kártya aktiválása.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'card_code' => 'required|string|max:50',
            'profile_name' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->cardService->activateCard(
                $request->card_code,
                $request->user(),
                $request->profile_name
            );

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Felhasználó kártyáinak lekérdezése.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $cards = $this->cardService->getUserCards($request->user());

            return response()->json([
                'success' => true,
                'cards' => $cards,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Kártya részletes adatainak lekérdezése.
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        try {
            $card = $request->user()->cards()->with('profile')->findOrFail($id);

            return response()->json([
                'success' => true,
                'card' => $card,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'A kártya nem található vagy nem a felhasználóhoz tartozik.',
            ], 404);
        }
    }

    /**
     * Kártya törlése (deaktiválása).
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        try {
            $result = $this->cardService->deactivateCard($id, $request->user());

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
