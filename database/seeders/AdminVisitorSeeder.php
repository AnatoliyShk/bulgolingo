<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the read-only "admin" demo account (name/password: admin) used to let
 * people click through the admin panel without granting real admin write
 * access or exposing other users' account information. Idempotent, so
 * re-running it just resets the account rather than duplicating it.
 */
class AdminVisitorSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'admin',
                'password' => 'admin',
                'role_id' => Role::named(RoleName::AdminVisitor)->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
