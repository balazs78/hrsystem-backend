<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_the_position_list(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $position = Position::create([
            'title' => 'Developer',
            'salary_grade' => 'IC-01',
            'description' => 'Builds and maintains software.',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/positions');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $position->id)
            ->assertJsonPath('data.0.title', 'Developer')
            ->assertJsonPath('data.0.salaryGrade', 'IC-01')
            ->assertJsonPath('data.0.description', 'Builds and maintains software.')
            ->assertJsonPath('data.0.headcount', 0);
    }

    public function test_authenticated_user_can_get_an_individual_position(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $position = Position::create([
            'title' => 'Developer',
            'salary_grade' => 'IC-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/positions/'.$position->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $position->id)
            ->assertJsonPath('data.title', 'Developer')
            ->assertJsonPath('data.salaryGrade', 'IC-01')
            ->assertJsonPath('data.description', null);
    }
}
