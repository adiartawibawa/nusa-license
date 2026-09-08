<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'id' => (string) Str::uuid7(),
            'name' => 'Admin',
            'email' => 'admin@mail.test',
            'role' => UserRole::SuperAdmin,
        ]);

        $this->call([
            NusaLicenseSeeder::class,
        ]);
    }
}
