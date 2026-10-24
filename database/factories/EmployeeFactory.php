<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female']);
        $firstName = $this->faker->firstName($gender);
        $lastName = $this->faker->lastName();

        // Generate a realistic corporate email (john.doe@techcorp.com)
        $email = Str::lower($firstName.'.'.$lastName.'@techcorp.com');
        // Ensure uniqueness if Faker picks the same name twice
        $email = $this->faker->unique()->safeEmail();

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => $gender,
            'email' => $email,
            'phone' => $this->faker->e164PhoneNumber(),
            'address' => $this->faker->address(),
            'birthdate' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'hired_at' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'on_leave']), // Weighted chance

            // Get random existing IDs (We assume Departments/Positions are already seeded)
            'department_id' => Department::inRandomOrder()->first()->id ?? 1,
            'position_id' => Position::inRandomOrder()->first()->id ?? 1,

            // Default manager to null (We will assign this in the Seeder to avoid errors)
            'manager_id' => null,
            'profile_image' => rand(0, 1) ? 'https://i.pravatar.cc/150?u='.$email : null, // We'll handle this in the Seeder for realism
        ];
    }
}
