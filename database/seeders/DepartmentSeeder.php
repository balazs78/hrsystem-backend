<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function runOld(): void
    {
        $departments = [
            // C-Suite / Admin
            ['name' => 'Executive Office',      'cost_center_code' => 'HQ-0001'],
            ['name' => 'Human Resources',       'cost_center_code' => 'HR-0010'],
            ['name' => 'Finance & Accounting',  'cost_center_code' => 'FI-0020'],

            // Engineering (The biggest cost center)
            ['name' => 'Engineering - Backend', 'cost_center_code' => 'EB-0001'],
            ['name' => 'Engineering - Frontend', 'cost_center_code' => 'EF-0002'],
            ['name' => 'DevOps & SRE',          'cost_center_code' => 'DO-0003'],
            ['name' => 'QA & Testing',          'cost_center_code' => 'QA-0004'],
            ['name' => 'Mobile Development',    'cost_center_code' => 'MD-0005'],

            // Product & Design
            ['name' => 'Product Management',    'cost_center_code' => 'PM-0001'],
            ['name' => 'UI/UX Design',          'cost_center_code' => 'UI-0002'],

            // Revenue
            ['name' => 'Sales & Business Dev',  'cost_center_code' => 'SB-0099'],
            ['name' => 'Marketing',             'cost_center_code' => 'MA-0088'],
            ['name' => 'Customer Success',      'cost_center_code' => 'CU-0077'],
        ];

        // We use upsert to make this idempotent.
        // Logic: Look for 'cost_center_code'. If found, update 'name'. If not, insert new.
        Department::upsert(
            $departments,
            ['cost_center_code'], // The unique column to check against
            ['name']              // The column to update if record exists
        );

    }

    public function run()
    {
        // 1. Define the Department Data
        $deptData = [
            ['name' => 'Executive Office',      'code' => 'HQ-0001'],
            ['name' => 'Human Resources',       'code' => 'HR-0010'],
            ['name' => 'Finance & Accounting',  'code' => 'FI-0020'],
            ['name' => 'Engineering - Backend', 'code' => 'EB-0001'],
            ['name' => 'Engineering - Frontend', 'code' => 'EF-0002'],
            ['name' => 'Sales & Business Dev',  'code' => 'SB-0099'],
            ['name' => 'Marketing',             'code' => 'MA-0088'],
        ];

        // 2. Use a Transaction for safety
        DB::transaction(function () use ($deptData) {

            foreach ($deptData as $data) {

                // A. Create the Department (without a manager initially)
                // We use updateOrCreate to keep it idempotent
                $department = Department::updateOrCreate(
                    ['cost_center_code' => $data['code']], // Search by Code
                    ['name' => $data['name']]              // Update Name if found
                );

                // B. Create a "Manager" for this department
                // We use the Employee Factory here.
                $manager = Employee::factory()->create([
                    'first_name' => 'Head of', // Temporary naming convention
                    'last_name' => $data['name'],
                    'email' => strtolower($data['code']).'@company.com',
                    'department_id' => $department->id, // Link employee to dept
                    // Assign a high-level position if you have a Position seeder/factory
                    'position_id' => Position::first()?->id ?? 1,
                ]);

                // C. Close the circle: Assign this employee as the Department Manager
                $department->update([
                    'manager_id' => $manager->id,
                ]);

                // D. (Optional) Create 5 regular employees for this department
                Employee::factory()->count(5)->create([
                    'department_id' => $department->id,
                    'manager_id' => $manager->id, // They report to the new manager
                ]);
            }
        });
    }
}
