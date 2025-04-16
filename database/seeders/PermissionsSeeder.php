<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Itt gyűjtjük ki az összes egyedi permission-t a router konfigurációból
        $routePermissions = [
            'edit cards',
            'edit profiles',
            'view admin',
            'manage cards',
            'manage card types',
            'view statistics',
        ];

        // Hozzuk létre az összes permission-t a Spatie-val
        foreach ($routePermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Feltételezzük, hogy van egy admin felhasználó, akinek ezeket a jogokat adjuk
        // Ha az email alapján akarod megtalálni a felhasználót, cseréld ki az id-t az email-re
        $user = User::find(1); // vagy User::where('email', 'admin@example.com')->first();

        if ($user) {
            // Minden engedélyt közvetlenül a felhasználóhoz rendelünk, nem role-on keresztül
            foreach ($routePermissions as $permission) {
                $user->givePermissionTo($permission);
            }

            $this->command->info('Permissions created and assigned to user successfully!');
        } else {
            $this->command->error('User not found!');
        }
    }
}
