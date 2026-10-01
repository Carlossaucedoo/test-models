<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'ana',
            'email' => 'ana@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::factory()->create([
            'name' => 'luis',
            'email' => 'luis@example.com',
            'password' => Hash::make('password123'),
        ]);

        User::factory(10)->create();
    }
}
