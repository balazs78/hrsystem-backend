<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_authenticate_and_receive_a_personal_access_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'browser',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id);
        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'browser',
        ]);

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
        $this->assertGuest('web');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();

        $this->assertGuest('web');
    }

    public function test_users_can_revoke_their_current_token_on_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('browser');

        $response = $this->withToken($token->plainTextToken)->postJson('/api/logout');

        $response->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);

        Auth::forgetGuards();
        $this->withToken($token->plainTextToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_web_session_authentication_does_not_authenticate_api_requests(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_changing_password_uses_the_bearer_user_revokes_tokens_and_returns_a_new_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('browser');

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/force-password-update', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);

        $newToken = $response->json('token');
        $this->assertIsString($newToken);
        $this->withToken($newToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        Auth::forgetGuards();
        $this->withToken($token->plainTextToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }
}
