<?php

namespace Database\Seeders;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseCategory;
use App\Domain\Course\Models\CourseLesson;
use App\Domain\Course\Models\CourseModule;
use App\Domain\Course\Models\LessonContent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear un usuario profesor si no existe
        $teacher = User::firstOrCreate(
            ['email' => 'profesor@profesor.com'],
            [
                'name' => 'Profesor Demo',
                'ci' => '12345678',
                'password' => Hash::make('dalcroze77aA@'),
                'role' => 3, // Teacher
                'active' => 1,
                'password_changed' => 1,
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'test@test.com'],
            [
                'name' => 'user Demo',
                'ci' => '12345688',
                'password' => Hash::make('dalcroze77aA@'),
                'role' => 2, // Student
                'active' => 1,
                'password_changed' => 1,
            ]
        );

        // Crear un usuario estudiante si no existe
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin Demo',
                'ci' => '12345600',
                'password' => Hash::make('dalcroze77aA@'),
                'role' => 1, // Admin
                'active' => 1,
                'password_changed' => 1,
            ]
        );

        // Crear categorías
        $categories = [
            [
                'name' => 'Ciberseguridad Básica',
                'slug' => 'ciberseguridad-basica',
                'description' => 'Fundamentos de ciberseguridad para principiantes',
                'icon' => 'shield',
            ],
            [
                'name' => 'Hacking Ético',
                'slug' => 'hacking-etico',
                'description' => 'Técnicas de pentesting y seguridad ofensiva',
                'icon' => 'terminal',
            ],
            [
                'name' => 'Seguridad en Redes',
                'slug' => 'seguridad-redes',
                'description' => 'Protección y configuración segura de redes',
                'icon' => 'network',
            ],
            [
                'name' => 'Criptografía',
                'slug' => 'criptografia',
                'description' => 'Principios y aplicaciones de criptografía',
                'icon' => 'lock',
            ],
        ];

        foreach ($categories as $categoryData) {
            CourseCategory::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        $cyberCategory = CourseCategory::where('slug', 'ciberseguridad-basica')->first();
        $hackingCategory = CourseCategory::where('slug', 'hacking-etico')->first();

        // Crear cursos de ejemplo
        $courses = [
            [
                'title' => 'Introducción a la Ciberseguridad',
                'slug' => 'introduccion-ciberseguridad',
                'description' => 'Aprende los conceptos fundamentales de la ciberseguridad, incluyendo amenazas comunes, medidas de protección y mejores prácticas para mantener tu información segura en el mundo digital.',
                'teacher_id' => $teacher->id,
                'category_id' => $cyberCategory->id,
                'difficulty_level' => 'beginner',
                'duration_hours' => 10,
                'price' => 0,
                'is_published' => true,
                'is_featured' => true,
                'rating' => 4.5,
            ],
            [
                'title' => 'Pentesting con Kali Linux',
                'slug' => 'pentesting-kali-linux',
                'description' => 'Domina las herramientas y técnicas de pentesting utilizando Kali Linux. Aprende a identificar vulnerabilidades y realizar auditorías de seguridad profesionales.',
                'teacher_id' => $teacher->id,
                'category_id' => $hackingCategory->id,
                'difficulty_level' => 'intermediate',
                'duration_hours' => 20,
                'price' => 49.99,
                'is_published' => true,
                'is_featured' => true,
                'rating' => 4.8,
            ],
            [
                'title' => 'Seguridad en Aplicaciones Web',
                'slug' => 'seguridad-aplicaciones-web',
                'description' => 'Conoce las vulnerabilidades más comunes en aplicaciones web (OWASP Top 10) y aprende a prevenirlas y mitigarlas efectivamente.',
                'teacher_id' => $teacher->id,
                'category_id' => $cyberCategory->id,
                'difficulty_level' => 'intermediate',
                'duration_hours' => 15,
                'price' => 39.99,
                'is_published' => true,
                'is_featured' => false,
                'rating' => 4.6,
            ],
        ];

        foreach ($courses as $courseData) {
            $course = Course::firstOrCreate(
                ['slug' => $courseData['slug']],
                $courseData
            );

            // Si el curso es nuevo, crear módulos y lecciones
            if ($course->wasRecentlyCreated) {
                $this->createCourseContent($course);
            }
        }
    }

    private function createCourseContent($course)
    {
        if ($course->slug === 'introduccion-ciberseguridad') {
            // Módulo 1
            $module1 = CourseModule::create([
                'course_id' => $course->id,
                'title' => 'Fundamentos de Ciberseguridad',
                'description' => 'Conceptos básicos y terminología esencial',
                'order_index' => 1,
                'is_published' => true,
            ]);

            // Lecciones del Módulo 1
            $lesson1 = CourseLesson::create([
                'module_id' => $module1->id,
                'title' => '¿Qué es la Ciberseguridad?',
                'description' => 'Introducción al mundo de la seguridad informática',
                'order_index' => 1,
                'duration_minutes' => 15,
                'is_preview' => true,
                'is_published' => true,
            ]);

            // Contenido de la lección
            LessonContent::create([
                'lesson_id' => $lesson1->id,
                'content_type' => 'text',
                'content_data' => [
                    'content' => '<h2>Bienvenido al mundo de la Ciberseguridad</h2>
                    <p>La ciberseguridad es la práctica de proteger sistemas, redes y programas de ataques digitales.</p>
                    <p>En este curso aprenderás:</p>
                    <ul>
                        <li>Conceptos fundamentales de seguridad</li>
                        <li>Tipos de amenazas y vulnerabilidades</li>
                        <li>Mejores prácticas de protección</li>
                        <li>Herramientas básicas de seguridad</li>
                    </ul>',
                    'format' => 'html',
                ],
                'order_index' => 1,
                'is_required' => true,
            ]);

            $lesson2 = CourseLesson::create([
                'module_id' => $module1->id,
                'title' => 'Tipos de Amenazas',
                'description' => 'Conoce las amenazas más comunes en ciberseguridad',
                'order_index' => 2,
                'duration_minutes' => 20,
                'is_preview' => false,
                'is_published' => true,
            ]);

            // Módulo 2
            $module2 = CourseModule::create([
                'course_id' => $course->id,
                'title' => 'Protección Básica',
                'description' => 'Medidas esenciales de protección',
                'order_index' => 2,
                'is_published' => true,
            ]);

            $lesson3 = CourseLesson::create([
                'module_id' => $module2->id,
                'title' => 'Contraseñas Seguras',
                'description' => 'Cómo crear y gestionar contraseñas fuertes',
                'order_index' => 1,
                'duration_minutes' => 15,
                'is_published' => true,
            ]);

            $lesson4 = CourseLesson::create([
                'module_id' => $module2->id,
                'title' => 'Autenticación de Dos Factores',
                'description' => 'Implementación de 2FA para mayor seguridad',
                'order_index' => 2,
                'duration_minutes' => 10,
                'is_published' => true,
            ]);
        }

        if ($course->slug === 'pentesting-kali-linux') {
            // Módulo 1
            $module1 = CourseModule::create([
                'course_id' => $course->id,
                'title' => 'Introducción a Kali Linux',
                'description' => 'Instalación y configuración inicial',
                'order_index' => 1,
                'is_published' => true,
            ]);

            $lesson1 = CourseLesson::create([
                'module_id' => $module1->id,
                'title' => 'Instalación de Kali Linux',
                'description' => 'Guía paso a paso para instalar Kali',
                'order_index' => 1,
                'duration_minutes' => 30,
                'is_preview' => true,
                'is_published' => true,
            ]);

            // Módulo 2
            $module2 = CourseModule::create([
                'course_id' => $course->id,
                'title' => 'Reconocimiento',
                'description' => 'Técnicas de recopilación de información',
                'order_index' => 2,
                'is_published' => true,
            ]);

            $lesson2 = CourseLesson::create([
                'module_id' => $module2->id,
                'title' => 'Escaneo con Nmap',
                'description' => 'Uso avanzado de Nmap para reconocimiento',
                'order_index' => 1,
                'duration_minutes' => 45,
                'is_published' => true,
            ]);
        }
    }
}
