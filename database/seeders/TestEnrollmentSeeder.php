<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Course\Models\CourseEnrollment;
use App\Models\User;

class TestEnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test student if it doesn't exist
        $testStudent = User::firstOrCreate(
            ['email' => 'student.test@vanguardia.com'],
            [
                'name' => 'Estudiante de Prueba',
                'ci' => '12345678', // Add CI field
                'password' => bcrypt('password'),
                'role' => 2, // Student role
                'active' => 1,
                'password_changed' => 1
            ]
        );

        // Create a test enrollment for certificate generation
        $testEnrollment = CourseEnrollment::firstOrCreate(
            ['id' => 1],
            [
                'student_id' => $testStudent->id,
                'course_id' => 1, // Assuming there's at least one course
                'status' => 'active',
                'progress_percentage' => 100,
                'enrolled_at' => now(),
                'completed_at' => now(),
            ]
        );

        $this->command->info("Test enrollment created: ID {$testEnrollment->id} for student {$testStudent->name}");
    }
}