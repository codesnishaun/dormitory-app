<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name'     => 'System Administrator',
            'email'    => 'admin@dora.test',
            'password' => 'admin123',
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Juan Dela Cruz',
            'email'    => 'juan@dora.test',
            'password' => 'tenant123',
            'role'     => 'tenant',
        ]);
    }
}
