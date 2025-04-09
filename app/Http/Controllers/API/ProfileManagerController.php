<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProfileManagerController extends Controller
{
    /**
     * A profil service példánya.
     *
     * @var ProfileService
     */
    protected $profileService;

    /**
     * Új kontroller példány létrehozása.
     *
     * @param ProfileService $profileService
     */
    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Felhasználó összes profiljának lekérése.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $profiles = $this->profileService->getUserProfiles($request->user()->id);

            return response()->json([
                'success' => true,
                'profiles' => $profiles
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Új profil létrehozása.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'jobTitle' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:100',
        ]);

        try {
            $profileData = $request->only(['name', 'jobTitle', 'company']);
            $profile = $this->profileService->createProfile($profileData, $request->user());

            return response()->json([
                'success' => true,
                'profile' => $profile,
                'message' => 'Profil sikeresen létrehozva.'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Profil adatok lekérdezése.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $profile = $this->profileService->getProfileById($id);

            return response()->json([
                'success' => true,
                'profile' => $profile
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Profil törlése.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            $this->profileService->deleteProfile($id, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Profil sikeresen törölve.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * A profil kártyához kapcsolása.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function linkToCard(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'card_code' => 'required|string'
        ]);

        try {
            $result = $this->profileService->linkProfileToCard($id, $request->card_code, $request->user()->id);

            return response()->json([
                'success' => true,
                'profile' => $result['profile'],
                'card' => $result['card'],
                'message' => 'Profil sikeresen kapcsolva a kártyához.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
