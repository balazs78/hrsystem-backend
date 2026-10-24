<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_the_department_list(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $department = Department::create([
            'name' => 'Engineering',
            'cost_center_code' => 'EN-0001',
        ]);
        $position = Position::create([
            'title' => 'Developer',
            'salary_grade' => 'IC-01',
        ]);
        Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/departments');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $department->id)
            ->assertJsonPath('data.0.name', 'Engineering')
            ->assertJsonPath('data.0.costCenterCode', 'EN-0001')
            ->assertJsonPath('data.0.stats.headcount', 1);
    }

    public function test_authenticated_user_can_get_an_individual_department(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $department = Department::create([
            'name' => 'Engineering',
            'cost_center_code' => 'EN-0001',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/departments/'.$department->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $department->id)
            ->assertJsonPath('data.name', 'Engineering')
            ->assertJsonPath('data.costCenterCode', 'EN-0001')
            ->assertJsonPath('data.stats.headcount', 0);
    }
}
