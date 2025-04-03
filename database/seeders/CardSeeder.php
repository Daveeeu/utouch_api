<?php

namespace Database\Seeders;

use App\Models\Card;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CardSeeder extends Seeder
{
    /**
     * Adatbázis seedelés futtatása.
     */
    public function run(): void
    {
        // 10 inaktív teszt kártya létrehozása
        for ($i = 1; $i <= 10; $i++) {
            Card::create([
                'code' => 'CARD-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'status' => 'inactive',
            ]);
        }

        // 5 véletlenszerű kódú kártya létrehozása
        for ($i = 1; $i <= 5; $i++) {
            Card::create([
                'code' => Str::upper(Str::random(8)),
                'status' => 'inactive',
            ]);
        }

        $this->command->info('15 teszt kártya sikeresen létrehozva.');
    }
}
