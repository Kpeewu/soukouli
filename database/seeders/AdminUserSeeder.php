<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Cree le compte administrateur initial d'une nouvelle installation client.
     * Ne s'execute jamais si un utilisateur existe deja.
     */
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        $password = env('INITIAL_ADMIN_PASSWORD') ?: Str::password(16);

        $admin = User::create([
            'username' => 'admin',
            'password' => $password,
        ]);
        $admin->assignRole('admin');

        $this->command->warn('=====================================================');
        $this->command->warn(' COMPTE ADMINISTRATEUR INITIAL CREE');
        $this->command->warn(' Identifiant : admin');
        $this->command->warn(" Mot de passe : {$password}");
        $this->command->warn(' -> Notez-le et changez-le des la premiere connexion.');
        $this->command->warn('=====================================================');
    }
}
