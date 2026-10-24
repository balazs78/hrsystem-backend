<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_the_employee_list(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create(['role' => 'Admin']);
        $department = Department::create([
            'name' => 'Engineering',
            'cost_center_code' => 'ENG-001',
        ]);
        $position = Position::create([
            'title' => 'Developer',
            'salary_grade' => 'IC-01',
        ]);
        $employee = Employee::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'status' => 'active',
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/employees');

        $response->assertOk()
            ->assertJsonPath('meta.total_users', 1)
            ->assertJsonPath('data.0.id', $employee->id)
            ->assertJsonPath('data.0.fullName', 'Ada Lovelace')
            ->assertJsonPath('data.0.status', 'Active')
            ->assertJsonPath('data.0.department.id', $department->id)
            ->assertJsonPath('data.0.position', 'Developer');
    }

    public function test_employee_list_rejects_invalid_position_filter_items(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/employees?position%5B0%5D=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position.0']);
    }

    public function test_authenticated_user_can_get_an_individual_employee(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create(['role' => 'Admin']);
        $department = Department::create([
            'name' => 'Engineering',
            'cost_center_code' => 'ENG-001',
        ]);
        $position = Position::create([
            'title' => 'Developer',
            'salary_grade' => 'IC-01',
        ]);
        $employee = Employee::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada.lovelace@example.com',
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/employees/'.$employee->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.fullName', 'Ada Lovelace')
            ->assertJsonPath('data.email', 'ada.lovelace@example.com')
            ->assertJsonPath('data.department.id', $department->id)
            ->assertJsonPath('data.position.title', 'Developer')
            ->assertJsonMissingPath('data.history');
    }
}
