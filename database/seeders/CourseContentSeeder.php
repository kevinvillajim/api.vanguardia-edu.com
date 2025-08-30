<?php

namespace Database\Seeders;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\CourseCategory;
use App\Domain\Course\Models\CourseModule;
use App\Domain\Course\Models\ModuleComponent;
use App\Domain\Course\Models\CourseMaterial;
use App\Domain\Course\Models\Quiz;
use App\Domain\Course\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear categoría si no existe
        $category = CourseCategory::firstOrCreate([
            'name' => 'Seguridad de la Información',
        ], [
            'description' => 'Cursos relacionados con ciberseguridad y protección de datos',
            'slug' => 'seguridad-informacion',
        ]);

        // Buscar un profesor (role 3) o crear uno temporal
        $teacher = User::where('role', 3)->first();
        if (!$teacher) {
            $teacher = User::where('role', 1)->first(); // Usar admin si no hay profesor
        }

        // Curso 1: Protección de datos personales
        $course1 = Course::firstOrCreate([
            'slug' => 'proteccion-datos-personales',
        ], [
            'title' => 'Protección de datos personales',
            'description' => 'Este curso abarca los aspectos fundamentales de la protección de datos personales y sus definiciones, así como medidas de protección, destacando su importancia y prevención.',
            'banner_image' => '/curso4.png',
            'teacher_id' => $teacher->id,
            'category_id' => $category->id,
            'difficulty_level' => 'beginner',
            'duration_hours' => 8,
            'price' => 0.00,
            'is_published' => true,
            'is_featured' => true,
        ]);

        // Materiales del curso 1
        CourseMaterial::create([
            'course_id' => $course1->id,
            'title' => 'Pdf Referencial',
            'description' => 'Este archivo es el PDF referencial que se utilizó para la creación del curso.',
            'type' => 'pdf',
            'file_url' => '/proteccionDatos.pdf',
            'file_name' => 'proteccionDatos.pdf',
            'order' => 1,
        ]);

        CourseMaterial::create([
            'course_id' => $course1->id,
            'title' => 'Ley Organica de Protección de Datos Personales',
            'description' => 'Ley actualizada y vigente, aprobada en mayo de 2021',
            'type' => 'pdf',
            'file_url' => '/lopdp.pdf',
            'file_name' => 'lopdp.pdf',
            'order' => 2,
        ]);

        // Módulos del curso 1
        $module1_1 = CourseModule::create([
            'course_id' => $course1->id,
            'title' => 'Unidad 1: Definiciones Importantes',
            'description' => 'En esta unidad aprenderemos las definiciones fundamentales de la protección de datos personales.',
            'order_index' => 1,
            'is_published' => true,
        ]);

        // Componentes del módulo 1.1
        ModuleComponent::create([
            'module_id' => $module1_1->id,
            'type' => 'banner',
            'title' => 'Bienvenida al Módulo',
            'content' => 'Bienvenido a la primera unidad del curso sobre protección de datos personales.',
            'rich_content' => '<h2>Bienvenida al Módulo</h2><p>En esta unidad exploraremos las definiciones fundamentales que necesitas conocer.</p>',
            'order' => 1,
            'is_mandatory' => true,
        ]);

        ModuleComponent::create([
            'module_id' => $module1_1->id,
            'type' => 'reading',
            'title' => '1.1 Datos Personales',
            'content' => 'Los datos personales son cualquier información concerniente a personas naturales identificadas o identificables.',
            'rich_content' => '<h3>1.1 Datos Personales</h3><p>Los datos personales son cualquier información concerniente a personas naturales identificadas o identificables. Esto incluye:</p><ul><li>Nombres y apellidos</li><li>Números de identificación</li><li>Datos de localización</li><li>Identificadores en línea</li></ul>',
            'duration' => 10,
            'order' => 2,
            'is_mandatory' => true,
        ]);

        ModuleComponent::create([
            'module_id' => $module1_1->id,
            'type' => 'reading',
            'title' => '1.2 Datos Sensibles',
            'content' => 'Los datos sensibles requieren protección especial debido a su naturaleza delicada.',
            'rich_content' => '<h3>1.2 Datos Sensibles</h3><p>Los datos sensibles son aquellos que requieren protección especial, como:</p><ul><li>Origen racial o étnico</li><li>Opiniones políticas</li><li>Convicciones religiosas</li><li>Datos de salud</li><li>Datos biométricos</li></ul>',
            'duration' => 15,
            'order' => 3,
            'is_mandatory' => true,
        ]);

        // Quiz para el módulo 1.1
        $quiz1_1 = Quiz::create([
            'module_id' => $module1_1->id,
            'title' => 'Evaluación Unidad 1',
            'description' => 'Evaluación sobre definiciones importantes en protección de datos',
            'time_limit' => 20,
            'max_attempts' => 3,
            'passing_score' => 70.00,
            'shuffle_questions' => true,
            'show_correct_answers' => true,
            'is_mandatory' => true,
            'order' => 4,
        ]);

        // Preguntas del quiz
        QuizQuestion::create([
            'quiz_id' => $quiz1_1->id,
            'question' => '¿Qué son los datos personales?',
            'type' => 'multiple_choice',
            'options' => json_encode([
                'A' => 'Solo el nombre y apellido',
                'B' => 'Cualquier información que identifique a una persona',
                'C' => 'Solo números de identificación',
                'D' => 'Solo información pública'
            ]),
            'correct_answer' => 'B',
            'points' => 25,
            'order' => 1,
        ]);

        QuizQuestion::create([
            'quiz_id' => $quiz1_1->id,
            'question' => '¿Cuáles son ejemplos de datos sensibles?',
            'type' => 'multiple_choice',
            'options' => json_encode([
                'A' => 'Nombre y dirección',
                'B' => 'Datos de salud y origen étnico',
                'C' => 'Número de teléfono',
                'D' => 'Correo electrónico'
            ]),
            'correct_answer' => 'B',
            'points' => 25,
            'order' => 2,
        ]);

        // Módulo 2 del curso 1
        $module1_2 = CourseModule::create([
            'course_id' => $course1->id,
            'title' => 'Unidad 2: Integrantes del Sistema de Protección de Datos Personales',
            'description' => 'Conoceremos los diferentes actores que participan en el sistema de protección de datos.',
            'order_index' => 2,
            'is_published' => true,
        ]);

        // Componentes del módulo 1.2
        $actors = [
            '2.1 Titular' => 'La persona natural a quien se refieren los datos personales.',
            '2.2 Responsable de tratamiento de datos personales' => 'Quien decide sobre el tratamiento de datos personales.',
            '2.3 Delegado de protección de datos' => 'Persona encargada de supervisar el cumplimiento de la normativa.',
            '2.4 Encargado del tratamiento de datos personales' => 'Quien trata los datos por cuenta del responsable.',
            '2.5 Destinatario' => 'Persona natural o jurídica que recibe comunicación de datos.',
            '2.6 Autoridad de Protección de Datos Personales' => 'Organismo encargado de velar por el cumplimiento de la ley.',
            '2.7 Consentimiento' => 'Manifestación de voluntad libre, específica e informada.'
        ];

        $order = 1;
        foreach ($actors as $title => $content) {
            ModuleComponent::create([
                'module_id' => $module1_2->id,
                'type' => 'reading',
                'title' => $title,
                'content' => $content,
                'rich_content' => "<h3>{$title}</h3><p>{$content}</p>",
                'duration' => 8,
                'order' => $order++,
                'is_mandatory' => true,
            ]);
        }

        // Quiz para el módulo 1.2
        $quiz1_2 = Quiz::create([
            'module_id' => $module1_2->id,
            'title' => 'Evaluación Unidad 2',
            'description' => 'Evaluación sobre los integrantes del sistema',
            'time_limit' => 25,
            'max_attempts' => 3,
            'passing_score' => 70.00,
            'shuffle_questions' => true,
            'show_correct_answers' => true,
            'is_mandatory' => true,
            'order' => $order,
        ]);

        // Curso 2: Introducción a la seguridad y fraudes financieros
        $course2 = Course::create([
            'title' => 'Introducción a la seguridad y fraudes financieros',
            'slug' => 'seguridad-fraudes-financieros',
            'description' => 'Principales ataques de ciberdelincuentes y estafadores, tipos de fraudes, métodos de prevención y manejo seguro de transacciones.',
            'banner_image' => '/curso5.jpeg',
            'teacher_id' => $teacher->id,
            'category_id' => $category->id,
            'difficulty_level' => 'intermediate',
            'duration_hours' => 10,
            'price' => 0.00,
            'is_published' => true,
            'is_featured' => true,
        ]);

        // Materiales del curso 2
        CourseMaterial::create([
            'course_id' => $course2->id,
            'title' => 'Guía de Fraudes Financieros',
            'description' => 'Manual completo sobre identificación y prevención de fraudes.',
            'type' => 'pdf',
            'file_url' => '/fraudes-financieros.pdf',
            'file_name' => 'fraudes-financieros.pdf',
            'order' => 1,
        ]);

        // Módulo del curso 2
        $module2_1 = CourseModule::create([
            'course_id' => $course2->id,
            'title' => 'Unidad 1: Principales Tipos de Fraudes en Canales Digitales Financieros',
            'description' => 'Identificación de los principales tipos de fraudes que ocurren en el ámbito digital financiero.',
            'order_index' => 1,
            'is_published' => true,
        ]);

        $fraudTypes = [
            '1.1 Introducción' => 'Los fraudes financieros digitales han aumentado significativamente en los últimos años.',
            '1.2 Phishing' => 'Técnica que busca obtener información confidencial haciéndose pasar por entidades confiables.',
            '1.3 Malware' => 'Software malicioso diseñado para dañar o obtener acceso no autorizado a sistemas.',
            '1.4 Robo de Identidad' => 'Uso no autorizado de información personal para cometer fraudes.',
            '1.5 Fraude en el comercio electrónico' => 'Estafas que ocurren durante transacciones en línea.'
        ];

        $order = 1;
        foreach ($fraudTypes as $title => $content) {
            ModuleComponent::create([
                'module_id' => $module2_1->id,
                'type' => $title === '1.1 Introducción' ? 'video' : 'reading',
                'title' => $title,
                'content' => $content,
                'rich_content' => "<h3>{$title}</h3><p>{$content}</p>",
                'file_url' => $title === '1.1 Introducción' ? '/videos/introduccion-fraudes.mp4' : null,
                'duration' => $title === '1.1 Introducción' ? 15 : 10,
                'order' => $order++,
                'is_mandatory' => true,
            ]);
        }

        // Curso 3: Introducción a la Ciberseguridad en Cooperativas
        $course3 = Course::create([
            'title' => 'Introducción a la Ciberseguridad en Cooperativas',
            'slug' => 'ciberseguridad-cooperativas',
            'description' => 'Curso especializado en ciberseguridad para el sector cooperativo, abarcando conceptos básicos, amenazas actuales y marco regulatorio aplicable a cooperativas financieras.',
            'banner_image' => '/curso6.png',
            'teacher_id' => $teacher->id,
            'category_id' => $category->id,
            'difficulty_level' => 'advanced',
            'duration_hours' => 12,
            'price' => 0.00,
            'is_published' => true,
            'is_featured' => true,
        ]);

        // Materiales del curso 3
        $course3Materials = [
            [
                'title' => 'Gestión de Riesgos de Ciberseguridad SEPS',
                'description' => 'Documento oficial de la Superintendencia de Economía Popular y Solidaria sobre gestión de riesgos de ciberseguridad.',
                'file_url' => '/Gestion-de-riesgos-de-ciberseguridad-v1-1.pdf',
            ],
            [
                'title' => 'Auditoría TI SEPS',
                'description' => 'Documento oficial de la Superintendencia de Economía Popular y Solidaria sobre auditorías a TI',
                'file_url' => '/Presentación-SEPS-Auditoría-de-TI.pdf',
            ],
            [
                'title' => 'Sistema de Gestión de Seguridad de la Información (SGSI) SEPS',
                'description' => 'Documento resumen de la SEPS del Estándar internacional para sistemas de gestión de seguridad de la información',
                'file_url' => '/Norma-ISO-27001.pdf',
            ]
        ];

        foreach ($course3Materials as $index => $material) {
            CourseMaterial::create([
                'course_id' => $course3->id,
                'title' => $material['title'],
                'description' => $material['description'],
                'type' => 'pdf',
                'file_url' => $material['file_url'],
                'order' => $index + 1,
            ]);
        }

        // Módulos del curso 3
        $course3Modules = [
            [
                'title' => 'Unidad 1: Introducción a la Ciberseguridad en Cooperativas',
                'description' => 'Conceptos fundamentales de ciberseguridad aplicados al sector cooperativo.',
                'topics' => [
                    '1.1 Conceptos Básicos de Ciberseguridad',
                    '1.2 Importancia en el Sector Cooperativo',
                    '1.3 Principales Amenazas Actuales'
                ]
            ],
            [
                'title' => 'Unidad 2: Panorama de Amenazas Cibernéticas',
                'description' => 'Análisis detallado de las principales amenazas que enfrentan las cooperativas.',
                'topics' => [
                    '2.1 Phishing y Malware',
                    '2.2 Ransomware y Ataques DDoS',
                    '2.3 Ingeniería Social y Fraude Digital'
                ]
            ],
            [
                'title' => 'Unidad 3: Marco Regulatorio y Cumplimiento',
                'description' => 'Normativas y controles requeridos para cooperativas financieras.',
                'topics' => [
                    '3.1 Normativas Locales e Internacionales',
                    '3.2 Controles de Seguridad Requeridos',
                    '3.3 Plan de Continuidad del Negocio'
                ]
            ]
        ];

        foreach ($course3Modules as $moduleIndex => $moduleData) {
            $module = CourseModule::create([
                'course_id' => $course3->id,
                'title' => $moduleData['title'],
                'description' => $moduleData['description'],
                'order_index' => $moduleIndex + 1,
                'is_published' => true,
            ]);

            foreach ($moduleData['topics'] as $topicIndex => $topic) {
                ModuleComponent::create([
                    'module_id' => $module->id,
                    'type' => 'reading',
                    'title' => $topic,
                    'content' => "Contenido detallado sobre {$topic}",
                    'rich_content' => "<h3>{$topic}</h3><p>Contenido detallado sobre {$topic}</p>",
                    'duration' => 12,
                    'order' => $topicIndex + 1,
                    'is_mandatory' => true,
                ]);
            }

            // Quiz para cada módulo
            Quiz::create([
                'module_id' => $module->id,
                'title' => "Evaluación {$moduleData['title']}",
                'description' => "Evaluación sobre {$moduleData['title']}",
                'time_limit' => 30,
                'max_attempts' => 3,
                'passing_score' => 70.00,
                'shuffle_questions' => true,
                'show_correct_answers' => true,
                'is_mandatory' => true,
                'order' => count($moduleData['topics']) + 1,
            ]);
        }

        $this->command->info('Cursos con estructura completa creados exitosamente!');
    }
}