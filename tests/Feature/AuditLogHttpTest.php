<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_audit_logs_with_related_user_and_employee(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create(['role' => 'Admin', 'name' => 'Admin User']);
        $department = Department::create([
            'name' => 'Engineering',
            'cost_center_code' => 'EN-0001',
        ]);
        $position = Position::create([
            'title' => 'Developer',
            'salary_grade' => 'IC-01',
        ]);
        $employee = Employee::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $auditLog = AuditLog::create([
            'user_id' => $admin->id,
            'employee_id' => $employee->id,
            'action' => 'updated',
            'old_value' => 'on_leave',
            'new_value' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/audit-logs');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $auditLog->id)
            ->assertJsonPath('data.0.action', 'updated')
            ->assertJsonPath('data.0.old_value', 'on_leave')
            ->assertJsonPath('data.0.new_value', 'active')
            ->assertJsonPath('data.0.user.id', $admin->id)
            ->assertJsonPath('data.0.user.name', 'Admin User')
            ->assertJsonPath('data.0.employee.id', $employee->id)
            ->assertJsonPath('data.0.employee.name', 'Ada Lovelace');
    }

    public function test_audit_log_filters_accept_camel_case_parameters(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        /** @var User&Authenticatable $otherUser */
        $otherUser = User::factory()->create(['role' => 'Admin']);
        $department = Department::create([
            'name' => 'Engineering',
            'cost_center_code' => 'EN-0001',
        ]);
        $position = Position::create([
            'title' => 'Developer',
            'salary_grade' => 'IC-01',
        ]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $otherEmployee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $createLog = function (User $user, Employee $target, string $createdAt): AuditLog {
            $log = AuditLog::create([
                'user_id' => $user->id,
                'employee_id' => $target->id,
                'action' => 'updated',
            ]);
            $log->created_at = $createdAt;
            $log->save();

            return $log;
        };

        $matchingLog = $createLog($admin, $employee, '2026-05-15 12:00:00');
        $createLog($otherUser, $employee, '2026-05-15 12:00:00');
        $createLog($admin, $otherEmployee, '2026-05-15 12:00:00');
        $createLog($admin, $employee, '2026-05-09 12:00:00');
        $createLog($admin, $employee, '2026-05-21 12:00:00');

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/audit-logs?'.http_build_query([
            'userId' => $admin->id,
            'employeeId' => $employee->id,
            'startDate' => '2026-05-10',
            'endDate' => '2026-05-20',
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingLog->id);
    }

    public function test_regular_user_cannot_get_audit_logs(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create(['role' => 'Employee']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/audit-logs')
            ->assertForbidden();
    }

    public function test_employee_lifecycle_audit_events_use_the_authenticated_user(): void
    {
        User::factory()->create(['role' => 'Employee']);
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $department = Department::create([
            'name' => 'Engineering',
            'cost_center_code' => 'EN-0001',
        ]);
        $position = Position::create([
            'title' => 'Developer',
            'salary_grade' => 'IC-01',
        ]);

        $this->actingAs($admin, 'sanctum');
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $employee->update(['first_name' => 'Updated']);
        $employee->delete();

        $this->assertDatabaseCount('audit_logs', 3);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'employee_id' => $employee->id,
        ]);
        $this->assertDatabaseMissing('audit_logs', ['user_id' => 1]);
    }
}
