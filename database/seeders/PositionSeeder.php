<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            // --- C-Suite (Executive Track) ---
            ['title' => 'Chief Executive Officer (CEO)', 'salary_grade' => 'EX-01'],
            ['title' => 'Chief Technology Officer (CTO)', 'salary_grade' => 'EX-02'],
            ['title' => 'VP of Engineering',             'salary_grade' => 'EX-03'],

            // --- Management Track (M) ---
            ['title' => 'Engineering Manager',           'salary_grade' => 'M-04'],
            ['title' => 'Product Director',              'salary_grade' => 'M-04'],
            ['title' => 'Team Lead',                     'salary_grade' => 'M-03'],
            ['title' => 'HR Manager',                    'salary_grade' => 'M-02'],

            // --- Engineering Track (IC - Individual Contributor) ---
            ['title' => 'Principal Software Engineer',   'salary_grade' => 'IC-06'],
            ['title' => 'Staff Software Engineer',       'salary_grade' => 'IC-05'],
            ['title' => 'Senior Backend Developer',      'salary_grade' => 'IC-04'],
            ['title' => 'Senior Frontend Developer',     'salary_grade' => 'IC-04'],
            ['title' => 'Mid-Level Backend Developer',   'salary_grade' => 'IC-03'],
            ['title' => 'Mid-Level Frontend Developer',  'salary_grade' => 'IC-03'],
            ['title' => 'Junior Software Developer',     'salary_grade' => 'IC-02'],
            ['title' => 'DevOps Engineer',               'salary_grade' => 'IC-04'],
            ['title' => 'QA Automation Engineer',        'salary_grade' => 'IC-03'],

            // --- Product & Design Track ---
            ['title' => 'Senior Product Manager',        'salary_grade' => 'IC-05'],
            ['title' => 'UI/UX Designer',                'salary_grade' => 'IC-03'],

            // --- Admin & Operations ---
            ['title' => 'Talent Acquisition Specialist', 'salary_grade' => 'OPS-03'],
            ['title' => 'Accountant',                    'salary_grade' => 'OPS-03'],
            ['title' => 'Office Administrator',          'salary_grade' => 'OPS-01'],
        ];

        // Idempotent Insert:
        // If the 'title' exists, we update the 'salary_grade'.
        // If it doesn't exist, we insert it.
        Position::upsert(
            $positions,
            ['title'],         // Unique key to check
            ['salary_grade']   // Columns to update
        );
    }
}
