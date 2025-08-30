<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseEnrollment;
use App\Models\User;

class TestTeacherCoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el profesor activo (pueden ser varios, tomar el que tenga role 3)
        $teacher = User::where('role', 3)->first();
        
        if (!$teacher) {
            $this->command->error('Profesor no encontrado');
            return;
        }
        
        $this->command->info("Usando profesor: {$teacher->name} (ID: {$teacher->id})");

        // Limpiar cursos anteriores del profesor
        Course::where('teacher_id', $teacher->id)->delete();
        $this->command->info("Cursos anteriores eliminados");

        // Crear cursos para el profesor
        $courses = [
            [
                'title' => 'Introducción a Laravel',
                'description' => 'Curso básico de Laravel para principiantes',
                'is_published' => true,
            ],
            [
                'title' => 'JavaScript Avanzado',
                'description' => 'Conceptos avanzados de JavaScript moderno',
                'is_published' => true,
            ],
            [
                'title' => 'React Fundamentals',
                'description' => 'Fundamentos de React y desarrollo frontend',
                'is_published' => true,
            ],
        ];

        $createdCourses = [];
        
        foreach ($courses as $courseData) {
            $course = Course::create([
                'teacher_id' => $teacher->id,
                'title' => $courseData['title'],
                'description' => $courseData['description'],
                'is_published' => $courseData['is_published'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $createdCourses[] = $course;
            $this->command->info("Creado curso: {$course->title}");
        }

        // Obtener estudiantes
        $students = User::where('role', 2)->get();
        
        // Inscribir algunos estudiantes a algunos cursos
        if ($students->count() > 0 && count($createdCourses) > 0) {
            // Estudiante 1: Inscrito en el primer curso con progreso 75%
            $student1 = $students->first();
            CourseEnrollment::create([
                'course_id' => $createdCourses[0]->id,
                'student_id' => $student1->id,
                'status' => 'active',
                'progress_percentage' => 75.50,
                'enrolled_at' => now()->subDays(10),
            ]);
            $this->command->info("Inscrito {$student1->name} en {$createdCourses[0]->title} (75% progreso)");

            // Estudiante 2: Inscrito en dos cursos
            if ($students->count() > 1) {
                $student2 = $students->get(1);
                CourseEnrollment::create([
                    'course_id' => $createdCourses[0]->id,
                    'student_id' => $student2->id,
                    'status' => 'completed',
                    'progress_percentage' => 100.00,
                    'enrolled_at' => now()->subDays(20),
                    'completed_at' => now()->subDays(5),
                ]);
                
                CourseEnrollment::create([
                    'course_id' => $createdCourses[1]->id,
                    'student_id' => $student2->id,
                    'status' => 'active',
                    'progress_percentage' => 45.25,
                    'enrolled_at' => now()->subDays(8),
                ]);
                
                $this->command->info("Inscrito {$student2->name} en {$createdCourses[0]->title} (completado) y {$createdCourses[1]->title} (45% progreso)");
            }

            // Estudiante 3: Sin inscripciones (disponible para todos los cursos)
            if ($students->count() > 2) {
                $student3 = $students->get(2);
                $this->command->info("{$student3->name} permanece sin inscripciones (disponible para todos los cursos)");
            }
        }
    }
}