<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'Admin',
            ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }
}
