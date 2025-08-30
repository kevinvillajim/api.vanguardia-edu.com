<?php

namespace Database\Seeders;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseCategory;
use App\Domain\Course\Models\CourseModule;
use App\Domain\Course\Models\ModuleComponent;
use App\Models\User;
use Illuminate\Database\Seeder;

class SimpleCourseSeeder extends Seeder
{
    public function run(): void
    {
        // Crear categoría si no existe
        $category = CourseCategory::firstOrCreate([
            'name' => 'Seguridad de la Información',
        ], [
            'description' => 'Cursos relacionados con ciberseguridad y protección de datos',
            'slug' => 'seguridad-informacion',
        ]);

        // Buscar un profesor
        $teacher = User::where('role', 3)->first() ?? User::where('role', 1)->first();

        // Verificar si ya existe el curso de prueba
        $existingCourse = Course::where('slug', 'curso-prueba-backup')->first();
        
        if ($existingCourse) {
            $this->command->info('Curso de prueba ya existe, usando el existente.');
            return;
        }

        // Crear curso de prueba con estructura del backup
        $course = Course::create([
            'title' => 'Curso de Prueba - Estilo Backup',
            'slug' => 'curso-prueba-backup',
            'description' => 'Curso de prueba que replica la estructura del backup para testing.',
            'banner_image' => '/curso4.png',
            'teacher_id' => $teacher->id,
            'category_id' => $category->id,
            'difficulty_level' => 'beginner',
            'duration_hours' => 4,
            'price' => 0.00,
            'is_published' => true,
            'is_featured' => true,
        ]);

        // Crear módulo de prueba
        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Unidad 1: Módulo de Prueba',
            'description' => 'Este es un módulo de prueba para verificar la funcionalidad.',
            'order_index' => 1,
            'is_published' => true,
        ]);

        // Crear componentes de prueba
        ModuleComponent::create([
            'module_id' => $module->id,
            'type' => 'banner',
            'title' => 'Bienvenida al Módulo',
            'content' => 'Bienvenido al módulo de prueba.',
            'rich_content' => '<h2>Bienvenida al Módulo</h2><p>Este es un módulo de prueba.</p>',
            'order' => 1,
            'is_mandatory' => true,
        ]);

        ModuleComponent::create([
            'module_id' => $module->id,
            'type' => 'reading',
            'title' => 'Lectura de Prueba',
            'content' => 'Este es un contenido de lectura de prueba.',
            'rich_content' => '<h3>Lectura de Prueba</h3><p>Contenido de ejemplo para testing.</p>',
            'duration' => 10,
            'order' => 2,
            'is_mandatory' => true,
        ]);

        ModuleComponent::create([
            'module_id' => $module->id,
            'type' => 'video',
            'title' => 'Video de Prueba',
            'content' => 'Video de ejemplo.',
            'file_url' => '/videos/ejemplo.mp4',
            'duration' => 5,
            'order' => 3,
            'is_mandatory' => false,
        ]);

        $this->command->info('Curso de prueba creado exitosamente con ID: ' . $course->id);
    }
}