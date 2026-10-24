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
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_business_records_and_view_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $records = [
            new Department,
            new Position,
            new Employee,
        ];

        foreach ($records as $record) {
            $this->assertTrue(Gate::forUser($admin)->allows('viewAny', $record));
            $this->assertTrue(Gate::forUser($admin)->allows('view', $record));
            $this->assertTrue(Gate::forUser($admin)->allows('create', $record));
            $this->assertTrue(Gate::forUser($admin)->allows('update', $record));
            $this->assertTrue(Gate::forUser($admin)->allows('delete', $record));
            $this->assertTrue(Gate::forUser($admin)->allows('restore', $record));
            $this->assertTrue(Gate::forUser($admin)->allows('forceDelete', $record));
        }

        $auditLog = new AuditLog;

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', $auditLog));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $auditLog));
        $this->assertFalse(Gate::forUser($admin)->allows('create', $auditLog));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $auditLog));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $auditLog));
    }

    public function test_auditor_can_view_records_but_cannot_change_them(): void
    {
        $auditor = User::factory()->create(['role' => 'Auditor']);
        $records = [
            new Department,
            new Position,
            new Employee,
            new AuditLog,
        ];

        foreach ($records as $record) {
            $this->assertTrue(Gate::forUser($auditor)->allows('viewAny', $record));
            $this->assertTrue(Gate::forUser($auditor)->allows('view', $record));
            $this->assertFalse(Gate::forUser($auditor)->allows('create', $record));
            $this->assertFalse(Gate::forUser($auditor)->allows('update', $record));
            $this->assertFalse(Gate::forUser($auditor)->allows('delete', $record));
        }
    }

    public function test_hr_manager_can_manage_business_records_but_cannot_restore_them(): void
    {
        $hrManager = User::factory()->create(['role' => 'HR Manager']);
        $records = [
            new Department,
            new Position,
            new Employee,
        ];

        foreach ($records as $record) {
            $this->assertTrue(Gate::forUser($hrManager)->allows('view', $record));
            $this->assertTrue(Gate::forUser($hrManager)->allows('create', $record));
            $this->assertTrue(Gate::forUser($hrManager)->allows('update', $record));
            $this->assertTrue(Gate::forUser($hrManager)->allows('delete', $record));
            $this->assertFalse(Gate::forUser($hrManager)->allows('restore', $record));
            $this->assertFalse(Gate::forUser($hrManager)->allows('forceDelete', $record));
        }
    }

    public function test_manager_can_view_only_direct_subordinates(): void
    {
        $manager = User::factory()->create(['role' => 'Manager']);
        $manager->employee_id = 10;
        $subordinate = new Employee(['manager_id' => 10]);
        $otherEmployee = new Employee(['manager_id' => 11]);

        $this->assertTrue(Gate::forUser($manager)->allows('viewAny', $subordinate));
        $this->assertTrue(Gate::forUser($manager)->allows('view', $subordinate));
        $this->assertFalse(Gate::forUser($manager)->allows('view', $otherEmployee));
        $this->assertFalse(Gate::forUser($manager)->allows('create', $subordinate));
        $this->assertFalse(Gate::forUser($manager)->allows('update', $subordinate));
        $this->assertFalse(Gate::forUser($manager)->allows('delete', $subordinate));
    }

    public function test_only_admins_can_manage_users_while_auditors_can_list_users(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $auditor = User::factory()->create(['role' => 'Auditor']);
        $target = User::factory()->make();

        foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
            $arguments = in_array($ability, ['view', 'update', 'delete'])
                ? [$target]
                : [User::class];

            $this->assertTrue(Gate::forUser($admin)->allows($ability, $arguments));
        }

        $this->assertTrue(Gate::forUser($auditor)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($auditor)->allows('view', $target));
        $this->assertFalse(Gate::forUser($auditor)->allows('create', User::class));
        $this->assertFalse(Gate::forUser($auditor)->allows('update', $target));
        $this->assertFalse(Gate::forUser($auditor)->allows('delete', $target));
    }

    public function test_form_requests_enforce_create_and_update_policies(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create(['role' => 'Auditor']);
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

        $this->actingAs($auditor, 'sanctum')
            ->postJson('/api/employees', [])
            ->assertForbidden();

        $this->putJson('/api/employees/'.$employee->id, [])
            ->assertForbidden();

        $this->putJson('/api/departments/'.$department->id, [])
            ->assertForbidden();

        $this->putJson('/api/positions/'.$position->id, [])
            ->assertForbidden();
    }
}
