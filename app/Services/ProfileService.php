<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Collection;

class ProfileService
{
    /**
     * Felhasználóhoz tartozó összes profil lekérdezése.
     *
     * @param int $userId
     * @return array
     */
    public function getUserProfiles(int $userId): array
    {
        $profiles = Profile::where('user_id', $userId)->get();

        // Profilok formázott adatainak visszaadása
        return $profiles->map(function ($profile) {
            $formattedProfile = $this->formatProfileData($profile);

            // Kapcsolódó kártya keresése (ahol a card.profile_id = profile.id)
            $card = Card::where('profile_id', $profile->id)->first();
            if ($card) {
                $formattedProfile['cardId'] = $card->id;
                $formattedProfile['cardCode'] = $card->code;
            } else {
                $formattedProfile['cardId'] = null;
                $formattedProfile['cardCode'] = null;
            }

            return $formattedProfile;
        })->toArray();
    }

    /**
     * Új profil létrehozása.
     *
     * @param array $profileData
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function createProfile(array $profileData, User $user): array
    {
        // Ellenőrizzük, hogy a felhasználó létrehozhat-e új profilt
        // Azok a profilok, amelyekhez nincs kártya rendelve
        $unlinkedProfilesCount = Profile::where('user_id', $user->id)
            ->whereDoesntHave('cards')  // Profilhoz nem tartozik kártya
            ->count();

        if ($unlinkedProfilesCount >= 5) {
            throw new Exception('Maximum 5 kártya nélküli profil hozható létre.');
        }

        // Tranzakció kezdése
        DB::beginTransaction();

        try {
            // Név felbontása vezeték- és keresztnévre
            $name = $profileData['name'];

            // Meta adatok létrehozása
            $metaData = [
                'firstName' => '',
                'lastName' => '',
                'jobTitle' => $profileData['jobTitle'] ?? null,
                'company' => $profileData['company'] ?? null,
                'settings' => [
                    'isPublic' => true,
                    'isPrimary' => false,
                    'customUrl' => Str::slug($name),
                    'theme' => 'default',
                    'language' => 'hu_HU'
                ],
                'multimedia' => [
                    'videoUrl' => null,
                    'documents' => [],
                    'portfolioItems' => []
                ]
            ];

            // Profil létrehozása az adatbázisban
            $profile = new Profile([
                'user_id' => $user->id,
                'name' => $name,
                'description' => null,
                'image' => null,
                'is_public' => true,
                'meta_data' => json_encode($metaData),
                'contact_info' => json_encode([]),
                'social_links' => json_encode([])
            ]);

            $profile->save();

            // Tranzakció véglegesítése
            DB::commit();

            $formattedProfile = $this->formatProfileData($profile);
            $formattedProfile['cardId'] = null;
            $formattedProfile['cardCode'] = null;

            return $formattedProfile;
        } catch (Exception $e) {
            // Tranzakció visszavonása hiba esetén
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Profil adatok lekérdezése azonosító alapján.
     *
     * @param int $profileId
     * @return array
     * @throws Exception
     */
    public function getProfileById(int $profileId): array
    {
        $profile = Profile::find($profileId);

        if (!$profile) {
            throw new Exception('A profil nem található.');
        }

        $formattedProfile = $this->formatProfileData($profile);

        // Kapcsolódó kártya keresése
        $card = Card::where('profile_id', $profile->id)->first();
        if ($card) {
            $formattedProfile['cardId'] = $card->id;
            $formattedProfile['cardCode'] = $card->code;
        } else {
            $formattedProfile['cardId'] = null;
            $formattedProfile['cardCode'] = null;
        }

        return $formattedProfile;
    }

    /**
     * Profil törlése.
     *
     * @param int $profileId
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function deleteProfile(int $profileId, int $userId): bool
    {
        // Profil ellenőrzése
        $profile = Profile::where('id', $profileId)
            ->where('user_id', $userId)
            ->first();

        if (!$profile) {
            throw new Exception('A profil nem található vagy nem a felhasználóhoz tartozik.');
        }

        // Ellenőrizzük, hogy a profilhoz tartozik-e kártya
        $card = Card::where('profile_id', $profile->id)->first();
        if ($card) {
            throw new Exception('A kártyához kötött profil nem törölhető. Előbb válassza le a kártyáról.');
        }

        // Profil törlése
        return $profile->delete();
    }

    /**
     * Profil kártyához kapcsolása.
     *
     * @param int $profileId
     * @param string $cardCode
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public function linkProfileToCard(int $profileId, string $cardCode, int $userId): array
    {
        // Profil ellenőrzése
        $profile = Profile::where('id', $profileId)
            ->where('user_id', $userId)
            ->first();

        if (!$profile) {
            throw new Exception('A profil nem található vagy nem a felhasználóhoz tartozik.');
        }

        // Ellenőrizzük, hogy a profilhoz tartozik-e már kártya
        $existingCard = Card::where('profile_id', $profile->id)->first();
        if ($existingCard) {
            throw new Exception('A profilhoz már kapcsolódik kártya.');
        }

        // Kártya keresése a kód alapján
        $card = Card::where('code', $cardCode)->first();

        if (!$card) {
            throw new Exception('A megadott kártyakód nem létezik.');
        }

        // Ellenőrizzük, hogy a kártya szabad-e
        if ($card->profile_id) {
            throw new Exception('A kártya már használatban van.');
        }

        // Tranzakció kezdése
        DB::beginTransaction();

        try {
            // Kártya összekapcsolása a profillal
            $card->profile_id = $profile->id;
            $card->activated_at = now();
            $card->status = 'active';
            $card->user_id = $userId;
            $card->save();

            // Tranzakció véglegesítése
            DB::commit();

            // Formázott adatok visszaadása
            $formattedProfile = $this->formatProfileData($profile);
            $formattedProfile['cardId'] = $card->id;
            $formattedProfile['cardCode'] = $card->code;

            return [
                'profile' => $formattedProfile,
                'card' => [
                    'id' => $card->id,
                    'code' => $card->code,
                    'status' => $card->status,
                    'activated_at' => $card->activated_at
                ]
            ];
        } catch (Exception $e) {
            // Tranzakció visszavonása hiba esetén
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Profil részletes adatainak lekérdezése.
     *
     * @param int $profileId
     * @return array
     * @throws Exception
     */
    public function getProfileDetail(int $profileId, bool $withCard = true): array
    {
        $profile = Profile::where('id', $profileId)
            ->first();

        if (!$profile) {
            throw new Exception('A profil nem található vagy nem a felhasználóhoz tartozik.');
        }
        // Profil adatainak strukturált formában való összeállítása
        $profileData = $this->formatProfileData($profile);

        // Profilhoz tartozó kártya keresése
        $card = Card::where('profile_id', $profile->id)->first();
        if ($card) {
            $profileData['card'] = [
                'id' => $card->id,
                'code' => $card->code,
                'status' => $card->status,
                'activated_at' => $card->activated_at
            ];
        }



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

            // Egyéb adatok JSON mezőkbe
            $extraData = [
                'contact_info' => isset($profileData['contacts']) ? json_encode($profileData['contacts']) : $profile->contact_info,
                'social_links' => isset($profileData['socialProfiles']) ? json_encode($profileData['socialProfiles']) : $profile->social_links,
            ];

            // Meta adatok kezelése
            $metaData = json_decode($profile->meta_data ?? '{}', true) ?: [];

            // Alap adatok META mezőbe mentése
            $metaData['firstName'] = $profileData['firstName'] ?? null;
            $metaData['lastName'] = $profileData['lastName'] ?? null;
            $metaData['jobTitle'] = $profileData['jobTitle'] ?? null;
            $metaData['company'] = $profileData['company'] ?? null;

            // Multimédia adatok mentése
            if (isset($profileData['multimedia'])) {
                $metaData['multimedia'] = $profileData['multimedia'];
            }

            // Beállítások mentése
            if (isset($profileData['settings'])) {
                $metaData['settings'] = $profileData['settings'];
            }

            // SEO beállítások mentése
            if (isset($profileData['seoSettings'])) {
                $metaData['seo'] = $profileData['seoSettings'];

                // Ha van OG kép fájl feltöltés, frissítjük az URL-t
                if (isset($profileData['ogImageFile'])) {
                    $metaData['seo']['ogImage'] = $profileData['ogImageFile'];
                }
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
     * Profil adatok formázása a frontend számára.
     *
     * @param Profile $profile
     * @return array
     */
    private function formatProfileData(Profile $profile): array
    {
        // Meta adatok kinyerése
        $metaData = json_decode($profile->meta_data ?? '{}', true) ?: [];

        // Eddigi adatformázás...
        $name = explode(' ', $profile->name ?? '', 2);
        $firstName = $metaData['firstName'] ?? ($name[1] ?? '');
        $lastName = $metaData['lastName'] ?? ($name[0] ?? '');
        $contacts = json_decode($profile->contact_info ?? '[]', true) ?: [];
        $socialProfiles = json_decode($profile->social_links ?? '[]', true) ?: [];

        // Alapértelmezett SEO beállítások
        $defaultSeoSettings = [
            'metaTitle' => '',
            'metaDescription' => '',
            'keywords' => '',
            'useCustomSocial' => false,
            'ogTitle' => '',
            'ogDescription' => '',
            'ogImage' => '',
            'noIndex' => false
        ];

        return [
            'id' => $profile->id,
            'name' => $profile->name,
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
            'seoSettings' => array_merge($defaultSeoSettings, $metaData['seo'] ?? []),
            'visits' => $profile->visits,
            'createdAt' => $profile->created_at,
            'updatedAt' => $profile->updated_at
        ];
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


}
