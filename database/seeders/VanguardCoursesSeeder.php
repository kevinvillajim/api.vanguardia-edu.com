<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Course\Models\Course;
use App\Models\User;
use Illuminate\Support\Str;

class VanguardCoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar el usuario profesor@profesor o usar ID 7 (basado en los logs)
        $teacher = User::where('email', 'profesor@profesor')->first();
        if (!$teacher) {
            // Si no existe, buscar un usuario con rol 3 (profesor)
            $teacher = User::where('role', 3)->first();
        }
        if (!$teacher) {
            $this->command->error('No se encontró un usuario profesor. Creando uno...');
            $teacher = User::create([
                'name' => 'Profesor VanguardIA',
                'email' => 'profesor@profesor',
                'password' => bcrypt('profesor123'),
                'role' => 3,
                'active' => 1,
                'password_changed' => 1,
            ]);
        }

        $teacherId = $teacher->id;

        // Curso 1: Fundamentos de Protección de Datos
        $course1 = Course::create([
            'title' => 'Fundamentos de Protección de Datos',
            'slug' => 'fundamentos-proteccion-datos',
            'description' => 'Aprende los conceptos fundamentales sobre protección de datos personales y sensibles. Este curso te proporcionará los conocimientos esenciales sobre las definiciones importantes en el ámbito de la protección de datos.',
            'teacher_id' => $teacherId,
            'category_id' => null,
            'difficulty_level' => 'beginner',
            'duration_hours' => 3,
            'price' => 0,
            'rating' => 4.8,
            'enrollment_count' => 0,
            'is_featured' => true,
            'is_published' => true,
            'banner_image' => '/c1Banner1.jpg',
        ]);

        // Curso 2: Introducción a la Seguridad y Fraudes Financieros
        $course2 = Course::create([
            'title' => 'Introducción a la Seguridad y Fraudes Financieros',
            'slug' => 'introduccion-seguridad-fraudes-financieros',
            'description' => 'Curso integral sobre los principales ataques de ciberdelincuentes y estafadores en el ámbito financiero. Aprenderás a identificar, prevenir y manejar diferentes tipos de fraudes en canales digitales.',
            'teacher_id' => $teacherId,
            'category_id' => null,
            'difficulty_level' => 'intermediate',
            'duration_hours' => 5,
            'price' => 0,
            'rating' => 4.9,
            'enrollment_count' => 0,
            'is_featured' => true,
            'is_published' => true,
            'banner_image' => '/c2Banner1.jpg',
        ]);

        // Curso 3: Ciberseguridad en Cooperativas
        $course3 = Course::create([
            'title' => 'Ciberseguridad en Cooperativas',
            'slug' => 'ciberseguridad-cooperativas',
            'description' => 'Especialización en ciberseguridad específicamente diseñada para el sector cooperativo. Comprende las amenazas particulares que enfrentan las cooperativas financieras y cómo proteger la información de los socios.',
            'teacher_id' => $teacherId,
            'category_id' => null,
            'difficulty_level' => 'advanced',
            'duration_hours' => 6,
            'price' => 0,
            'rating' => 4.7,
            'enrollment_count' => 0,
            'is_featured' => true,
            'is_published' => true,
            'banner_image' => '/c3Banner1.jpg',
        ]);

        $this->command->info('✅ Cursos de VanguardIA creados exitosamente:');
        $this->command->info("   • {$course1->title} (ID: {$course1->id})");
        $this->command->info("   • {$course2->title} (ID: {$course2->id})");
        $this->command->info("   • {$course3->title} (ID: {$course3->id})");
        $this->command->info("   • Asignados al profesor: {$teacher->name} (ID: {$teacher->id})");
    }
}