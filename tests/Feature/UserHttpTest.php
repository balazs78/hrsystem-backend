<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_delete_users(): void
    {
        /** @var User&Authenticatable $manager */
        $manager = User::factory()->create(['role' => 'Manager']);
        $target = User::factory()->create();

        $this->actingAs($manager, 'sanctum')
            ->deleteJson('/api/users/'.$target->id)
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_admin_can_delete_users(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $target = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/users/'.$target->id)
            ->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }
}
