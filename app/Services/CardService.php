<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class CardService
{
    /**
     * Kártya aktiválása a megadott kód alapján.
     *
     * @param string $cardCode
     * @param User $user
     * @param string|null $profileName
     * @return array
     * @throws Exception
     */
    public function activateCard(string $cardCode, User $user, ?string $profileName = null): array
    {
        // Kártya keresése a kód alapján
        $card = Card::where('code', $cardCode)->first();

        if (!$card) {
            throw new Exception('A megadott kártyakód nem található.');
        }

        if (!$card->isActivatable()) {
            throw new Exception('Ez a kártya már aktiválva van vagy lejárt.');
        }

        // Tranzakció kezdése
        DB::beginTransaction();

        try {
            // Alapértelmezett profil név beállítása, ha nincs megadva
            if (!$profileName) {
                $profileName = 'Profil - ' . $card->code;
            }

            // Új profil létrehozása
            $profile = Profile::createDefault(
                $user->id,
                $profileName,
                'personal'
            );

            // Kártya aktiválása
            $success = $card->activateCard($user->id, $profile->id);

            if (!$success) {
                throw new Exception('Nem sikerült aktiválni a kártyát.');
            }

            // Tranzakció véglegesítése
            DB::commit();

            return [
                'success' => true,
                'card' => $card->load('profile'),
                'message' => 'A kártya sikeresen aktiválva lett.'
            ];
        } catch (Exception $e) {
            // Tranzakció visszavonása hiba esetén
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Felhasználó által aktivált kártyák lekérdezése.
     *
     * @param User $user
     * @return mixed
     */
    public function getUserCards(User $user)
    {
        return Card::where('user_id', $user->id)
            ->with('profile')
            ->orderBy('activated_at', 'desc')
            ->get();
    }

    /**
     * Kártya törlése (deaktiválás).
     *
     * @param string $cardId
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function deactivateCard(string $cardId, User $user): array
    {
        // Kártya keresése azonosító alapján
        $card = Card::where('id', $cardId)
            ->where('user_id', $user->id)
            ->first();

        if (!$card) {
            throw new Exception('A megadott kártya nem található vagy nem a felhasználóhoz tartozik.');
        }

        // Tranzakció kezdése
        DB::beginTransaction();

        try {
            // Kapcsolódó profil törlése (soft delete)
            if ($card->profile) {
                $card->profile->delete();
            }

            // Kártya visszaállítása inaktív állapotba
            $card->status = 'inactive';
            $card->user_id = null;
            $card->profile_id = null;
            $card->activated_at = null;
            $card->save();

            // Tranzakció véglegesítése
            DB::commit();

            return [
                'success' => true,
                'message' => 'A kártya sikeresen deaktiválva lett.'
            ];
        } catch (Exception $e) {
            // Tranzakció visszavonása hiba esetén
            DB::rollBack();
            throw $e;
        }
    }
}
