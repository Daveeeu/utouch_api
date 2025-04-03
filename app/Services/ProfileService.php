<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class ProfileService
{
    /**
     * Profil részletes adatainak lekérdezése.
     *
     * @param int $profileId
     * @return array
     * @throws Exception
     */
    public function getProfileDetail(int $profileId): array
    {
        $profile = Profile::where('id', $profileId)
            ->first();

        if (!$profile) {
            throw new Exception('A profil nem található vagy nem a felhasználóhoz tartozik.');
        }

        // Profilhoz tartozó kártya azonosítója lekérdezése
        $card = $profile->card;
        if (!$card) {
            throw new Exception('A profilhoz nem tartozik kártya.');
        }

        // Profil adatainak strukturált formában való összeállítása
        $profileData = $this->formatProfileData($profile);
        $profileData['card'] = [
            'id' => $card->id,
            'code' => $card->code,
            'status' => $card->status,
            'activated_at' => $card->activated_at
        ];

        return $profileData;
    }

    /**
     * Profil frissítése.
     *
     * @param int $profileId
     * @param array $profileData
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function updateProfile(int $profileId, array $profileData, User $user): array
    {
        // Profil ellenőrzése
        $profile = Profile::where('id', $profileId)
            ->where('user_id', $user->id)
            ->first();

        if (!$profile) {
            throw new Exception('A profil nem található vagy nem a felhasználóhoz tartozik.');
        }

        // Egyedi URL ellenőrzése, ha van
        if (isset($profileData['settings']['customUrl']) &&
            !$this->isCustomUrlAvailable($profileData['settings']['customUrl'], $profileId)) {
            throw new Exception('A megadott egyedi URL már foglalt. Kérjük, válasszon másikat.');
        }

        // Tranzakció kezdése
        DB::beginTransaction();

        try {
            // Alap profil adatok frissítése
            $basicData = [
                'name' => trim(($profileData['firstName'] ?? '') . ' ' . ($profileData['lastName'] ?? '')),
                'description' => $profileData['bio'] ?? null,
                'is_public' => $profileData['settings']['isPublic'] ?? true,
            ];

            // Profilkép URL frissítése, ha van
            if (isset($profileData['photo'])) {
                $basicData['image'] = $profileData['photo'];
            }

            // Kontakt adatok, közösségi média, multimédia és beállítások JSON-ként tárolása
            $extraData = [
                'contact_info' => isset($profileData['contacts']) ? json_encode($profileData['contacts']) : $profile->contact_info,
                'social_links' => isset($profileData['socialProfiles']) ? json_encode($profileData['socialProfiles']) : $profile->social_links,
            ];

            // További adatok bővített JSON mezőként tárolása
            $metaData = json_decode($profile->meta_data ?? '{}', true) ?: [];

            // Alap adatok META mezőbe mentése (redundancia a könnyebb lekérdezésért)
            $metaData['firstName'] = $profileData['firstName'] ?? null;
            $metaData['lastName'] = $profileData['lastName'] ?? null;
            $metaData['jobTitle'] = $profileData['jobTitle'] ?? null;
            $metaData['company'] = $profileData['company'] ?? null;

            // Multimédia adatok mentése
            if (isset($profileData['multimedia'])) {
                $metaData['multimedia'] = $profileData['multimedia'];
            }

            // Portfólió képek hozzáadása, ha vannak
            if (isset($profileData['portfolioImages'])) {
                $metaData['multimedia']['portfolioItems'] = array_map(
                    function ($item, $index) use ($profileData, $metaData) {
                        // Az eredeti portfolioItems tömb megőrzése
                        $originalItems = $metaData['multimedia']['portfolioItems'] ?? [];

                        // Megkeressük az adott indexű elemet, vagy üres tömböt használunk
                        $existingItem = $originalItems[$index] ?? [];

                        // Egyesítjük a meglévő és új adatokat, az új kép URL-t felülírva
                        return array_merge($existingItem, $profileData['portfolioImages'][$index] ?? []);
                    },
                    $profileData['portfolioImages'],
                    array_keys($profileData['portfolioImages'])
                );
            }

            // Dokumentumok hozzáadása, ha vannak
            if (isset($profileData['documents'])) {
                $metaData['multimedia']['documents'] = array_merge(
                    $metaData['multimedia']['documents'] ?? [],
                    $profileData['documents']
                );
            }

            // Beállítások mentése
            if (isset($profileData['settings'])) {
                $metaData['settings'] = $profileData['settings'];
            }

            $extraData['meta_data'] = json_encode($metaData);

            // Profil frissítése
            $profile->update(array_merge($basicData, $extraData));

            // Tranzakció véglegesítése
            DB::commit();

            return $this->formatProfileData($profile->fresh());
        } catch (Exception $e) {
            // Tranzakció visszavonása hiba esetén
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ellenőrzi, hogy az egyedi URL elérhető-e.
     *
     * @param string $customUrl
     * @param int|null $excludeProfileId
     * @return bool
     */
    public function isCustomUrlAvailable(string $customUrl, ?int $excludeProfileId = null): bool
    {
        $query = Profile::whereRaw("JSON_EXTRACT(meta_data, '$.settings.customUrl') = ?", [$customUrl]);

        if ($excludeProfileId) {
            $query->where('id', '!=', $excludeProfileId);
        }

        return $query->count() === 0;
    }

    /**
     * Profil adatok formázása a frontend számára.
     *
     * @param Profile $profile
     * @return array
     */
    private function formatProfileData(Profile $profile): array
    {
        // Meta adatok kinyerése
        $metaData = json_decode($profile->meta_data ?? '{}', true) ?: [];

        // Név szétbontása vezeték és keresztnévre
        $name = explode(' ', $profile->name ?? '', 2);
        $firstName = $metaData['firstName'] ?? ($name[1] ?? '');
        $lastName = $metaData['lastName'] ?? ($name[0] ?? '');

        // Kontakt adatok deserialization
        $contacts = json_decode($profile->contact_info ?? '[]', true) ?: [];

        // Közösségi média linkek deserialization
        $socialProfiles = json_decode($profile->social_links ?? '[]', true) ?: [];

        // Formázott profil adatok
        return [
            'id' => $profile->id,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'jobTitle' => $metaData['jobTitle'] ?? null,
            'company' => $metaData['company'] ?? null,
            'bio' => $profile->description,
            'photoUrl' => $profile->image,
            'contacts' => $contacts,
            'socialProfiles' => $socialProfiles,
            'multimedia' => $metaData['multimedia'] ?? [
                    'videoUrl' => null,
                    'documents' => [],
                    'portfolioItems' => []
                ],
            'settings' => $metaData['settings'] ?? [
                    'isPublic' => $profile->is_public,
                    'isPrimary' => false,
                    'customUrl' => Str::slug($profile->name),
                    'theme' => 'default',
                    'language' => 'hu_HU'
                ],
            'visits' => $profile->visits,
            'createdAt' => $profile->created_at,
            'updatedAt' => $profile->updated_at
        ];
    }
}
