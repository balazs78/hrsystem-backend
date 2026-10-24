<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_changed_at' => now(),
        ]);
        $roles = ['Admin', 'Manager', 'HR Manager', 'Auditor'];
        foreach ($roles as $role) {
            User::factory()->create([
                'name' => $role.' User',
                'email' => strtolower(str_replace(' ', '', $role)).'@example.com',
                'role' => $role,
                'password_changed_at' => now(),
            ]);
        }
    }
}
