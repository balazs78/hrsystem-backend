<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create the CEO (The Root Node)
        // We force specific values to ensure we have one "Boss"
        $ceo = Employee::factory()->create([
            'first_name' => 'Richard',
            'last_name' => 'Hendricks',
            'gender' => 'male',
            'email' => 'richard@piedpiper.com',
            'position_id' => Position::where('title', 'CEO')->first()?->id ?? 1,
            'department_id' => Department::where('cost_center_code', 'HQ-001')->first()?->id ?? 1,
            'manager_id' => null, // CEO has no manager
        ]);

        // 2. Create Middle Management (Direct reports to CEO)
        // Let's say we have 5 Directors/VPs
        $managers = Employee::factory()
            ->count(5)
            ->create([
                'manager_id' => $ceo->id, // They report to Richard
                'status' => 'active',
            ]);

        // 3. Create Regular Staff (Report to the Managers)
        // We create 50 employees
        Employee::factory()
            ->count(50)
            ->make() // Note: use MAKE() first, not CREATE()
            ->each(function ($employee) use ($managers) {

                // Assign a random manager from the list above
                $employee->manager_id = $managers->random()->id;

                // Now save it to DB
                $employee->save();
            });

        // 4. (Optional) Create some "Terminated" employees for history checking
        Employee::factory()->count(5)->create([
            'manager_id' => $managers->random()->id,
            'status' => 'terminated',
            'deleted_at' => now(), // Soft deleted
        ]);

        // 4. (Optional) Create some "Contractor" employees
        for ($i = 0; $i < 10; $i++) {
            Employee::factory()->create([
                'manager_id' => $managers->random()->id,
                'status' => 'active',
                'contract_end_date' => now()->addMonths(rand(1, 12)), // Random contract end date within a year
            ]);
        }

        // 5. Expiring contracts
        for ($i = 0; $i < 6; $i++) {
            Employee::factory()->create([
                'manager_id' => $managers->random()->id,
                'contract_end_date' => now()->addDays(rand(1, 60)), // Random contract end date within 2 months
            ]);
        }

        // Setting the user_id for the first manager employee (Head of Executive Office) to link with the Admin user created in UserSeeder
        User::find(3)->update([
            'employee_id' => '1',
        ]);

    }
}
