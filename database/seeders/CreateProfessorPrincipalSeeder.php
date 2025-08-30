<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateProfessorPrincipalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar si existe usuario con ID 7
        $teacherPrincipal = User::find(7);
        
        if (!$teacherPrincipal) {
            // Crear usuario profesor principal
            $teacherPrincipal = User::create([
                'name' => 'Profesor Principal',
                'email' => 'profesor.principal@vanguardia.com',
                'password' => Hash::make('password123'),
                'ci' => '9988776655',
                'role' => 3, // Rol 3 para profesor
                'active' => true,
                'password_changed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("Creado profesor principal: {$teacherPrincipal->name} (ID: {$teacherPrincipal->id})");
        } else {
            // Actualizar el usuario existente para que sea profesor si no lo es
            if ($teacherPrincipal->role !== 3) {
                $teacherPrincipal->update(['role' => 3]);
                $this->command->info("Usuario ID 7 actualizado a rol profesor: {$teacherPrincipal->name}");
            } else {
                $this->command->info("Usando profesor existente: {$teacherPrincipal->name} (ID: {$teacherPrincipal->id})");
            }
        }

        // Limpiar cursos anteriores del profesor principal
        Course::where('teacher_id', $teacherPrincipal->id)->delete();
        $this->command->info("Cursos anteriores del profesor principal eliminados");

        // Crear cursos para el profesor principal
        $courses = [
            [
                'title' => 'Python para Principiantes',
                'description' => 'Aprende Python desde cero con ejemplos prácticos',
                'is_published' => true,
            ],
            [
                'title' => 'Base de Datos MySQL',
                'description' => 'Diseño y administración de bases de datos MySQL',
                'is_published' => true,
            ],
            [
                'title' => 'Desarrollo Web Full Stack',
                'description' => 'Curso completo de desarrollo web con tecnologías modernas',
                'is_published' => true,
            ],
            [
                'title' => 'Análisis de Datos',
                'description' => 'Curso de análisis de datos con Python y pandas',
                'is_published' => true,
            ],
        ];

        $createdCourses = [];
        
        foreach ($courses as $courseData) {
            $course = Course::create([
                'teacher_id' => $teacherPrincipal->id,
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
        
        // Inscribir estudiantes en algunos cursos del profesor principal
        if ($students->count() > 0 && count($createdCourses) > 0) {
            // Estudiante 1: Python + Base de Datos
            if ($students->count() >= 1) {
                $student1 = $students->get(0);
                CourseEnrollment::create([
                    'course_id' => $createdCourses[0]->id, // Python
                    'student_id' => $student1->id,
                    'status' => 'active',
                    'progress_percentage' => 85.75,
                    'enrolled_at' => now()->subDays(15),
                ]);
                
                CourseEnrollment::create([
                    'course_id' => $createdCourses[1]->id, // MySQL
                    'student_id' => $student1->id,
                    'status' => 'completed',
                    'progress_percentage' => 100.00,
                    'enrolled_at' => now()->subDays(25),
                    'completed_at' => now()->subDays(3),
                ]);
                
                $this->command->info("Inscrito {$student1->name} en Python (85% progreso) y MySQL (completado)");
            }

            // Estudiante 2: Full Stack
            if ($students->count() >= 2) {
                $student2 = $students->get(1);
                CourseEnrollment::create([
                    'course_id' => $createdCourses[2]->id, // Full Stack
                    'student_id' => $student2->id,
                    'status' => 'active',
                    'progress_percentage' => 62.30,
                    'enrolled_at' => now()->subDays(12),
                ]);
                
                $this->command->info("Inscrito {$student2->name} en Full Stack (62% progreso)");
            }

            // Estudiante 3: Sin inscripciones (disponible para todos)
            if ($students->count() >= 3) {
                $student3 = $students->get(2);
                $coursesCount = count($createdCourses);
                $this->command->info("{$student3->name} permanece sin inscripciones (disponible para {$coursesCount} cursos)");
            }
        }
    }
}