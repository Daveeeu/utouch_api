<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProfileController extends Controller
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
     * Profil adatok lekérdezése.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $profile = $this->profileService->getProfileDetail($id, $request->user());

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
     * Profil frissítése.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $request->merge([
            'contacts' => is_string($request->contacts) ? json_decode($request->contacts, true) : $request->contacts,
            'socialProfiles' => is_string($request->socialProfiles) ? json_decode($request->socialProfiles, true) : $request->socialProfiles,
            'multimedia' => is_string($request->multimedia) ? json_decode($request->multimedia, true) : $request->multimedia,
            'settings' => is_string($request->settings) ? json_decode($request->settings, true) : $request->settings,
        ]);
        $request->validate([
            'firstName' => 'nullable|string|max:100',
            'lastName' => 'nullable|string|max:100',
            'jobTitle' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'contacts' => 'nullable|array',
            'socialProfiles' => 'nullable|array',
            'multimedia' => 'nullable|array',
            'settings' => 'nullable|array'
        ]);

        try {
            $profileData = $request->only([
                'firstName', 'lastName', 'jobTitle', 'company', 'bio',
                'contacts', 'socialProfiles', 'multimedia', 'settings'
            ]);

            // Profilkép feltöltése, ha van
            if ($request->hasFile('photo')) {
                $profileData['photo'] = $this->uploadProfilePhoto($request);
            }

            // Dokumentumok feltöltése, ha vannak
            if ($request->hasFile('documents')) {
                $profileData['documents'] = $this->uploadDocuments($request);
            }

            // Portfólió képek feltöltése, ha vannak
            if ($request->hasFile('portfolioImages')) {
                $profileData['portfolioImages'] = $this->uploadPortfolioImages($request);
            }

            $profile = $this->profileService->updateProfile($id, $profileData, $request->user());

            return response()->json([
                'success' => true,
                'profile' => $profile,
                'message' => 'Profil sikeresen frissítve.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Egyedi URL ellenőrzése.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkCustomUrl(Request $request): JsonResponse
    {
        $request->validate([
            'customUrl' => 'required|string|max:100',
            'profileId' => 'nullable|integer'
        ]);

        $isAvailable = $this->profileService->isCustomUrlAvailable(
            $request->customUrl,
            $request->profileId
        );

        return response()->json([
            'success' => true,
            'available' => $isAvailable
        ]);
    }

    /**
     * Profilkép feltöltése.
     *
     * @param Request $request
     * @return string
     */
    private function uploadProfilePhoto(Request $request): string
    {
        $file = $request->file('photo');
        $path = $file->store('profile-photos', 'public');
        return Storage::url($path);
    }

    /**
     * Dokumentumok feltöltése.
     *
     * @param Request $request
     * @return array
     */
    private function uploadDocuments(Request $request): array
    {
        $documents = [];
        foreach ($request->file('documents') as $file) {
            $path = $file->store('profile-documents', 'public');
            $documents[] = [
                'name' => $file->getClientOriginalName(),
                'url' => Storage::url($path),
                'size' => $file->getSize(),
                'type' => $file->getMimeType()
            ];
        }
        return $documents;
    }

    /**
     * Portfólió képek feltöltése.
     *
     * @param Request $request
     * @return array
     */
    private function uploadPortfolioImages(Request $request): array
    {
        $images = [];
        foreach ($request->file('portfolioImages') as $key => $file) {
            $path = $file->store('portfolio-images', 'public');
            $images[$key] = [
                'imageUrl' => Storage::url($path)
            ];
        }
        return $images;
    }
}
