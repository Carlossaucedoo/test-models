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
        User::factory()->create([
            'username' => 'ana',
            'email' => 'ana@example.com',
            'password' => 'password123',
        ]);

        User::factory()->create([
            'username' => 'luis',
            'email' => 'luis@example.com',
            'password' => 'password123',
        ]);

        User::factory(10)->create();
    }
}
