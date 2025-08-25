<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Course\Models\Course;
use Illuminate\Support\Facades\DB;

class Course3ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar el curso de Ciberseguridad en Cooperativas
        $course = Course::where('slug', 'ciberseguridad-cooperativas')->first();
        
        if (!$course) {
            $this->command->error('Curso "Ciberseguridad en Cooperativas" no encontrado');
            return;
        }

        // Crear Unidad 1
        $unit1 = DB::table('course_units')->insertGetId([
            'course_id' => $course->id,
            'title' => 'Unidad 1: Introducción a la Ciberseguridad en Cooperativas',
            'description' => 'Fundamentos de ciberseguridad aplicados específicamente al sector cooperativo',
            'banner_image' => '/c3Banner1.jpg',
            'order_index' => 1,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear módulos para Unidad 1
        $modules = [
            [
                'title' => '1.1 Conceptos Básicos de Ciberseguridad',
                'description' => 'Fundamentos y principios de la ciberseguridad',
                'order' => 1,
            ],
            [
                'title' => '1.2 Importancia en el Sector Cooperativo',
                'description' => 'Por qué la ciberseguridad es crucial en cooperativas',
                'order' => 2,
            ],
            [
                'title' => '1.3 Principales Amenazas Actuales',
                'description' => 'Amenazas específicas que enfrentan las cooperativas',
                'order' => 3,
            ],
        ];

        $moduleIds = [];
        foreach ($modules as $moduleData) {
            $moduleIds[] = DB::table('course_modules')->insertGetId([
                'course_id' => $course->id,
                'title' => $moduleData['title'],
                'description' => $moduleData['description'],
                'order_index' => $moduleData['order'],
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Componentes del Módulo 1 - Conceptos Básicos
        $components1 = [
            [
                'module_id' => $moduleIds[0],
                'type' => 'banner',
                'title' => 'Banner Unidad 1',
                'content' => json_encode([
                    'img' => '/c3Banner1.jpg',
                    'title' => 'Unidad 1: Introducción a la Ciberseguridad en Cooperativas'
                ]),
                'order' => 1,
            ],
            [
                'module_id' => $moduleIds[0],
                'type' => 'reading',
                'title' => '1.1 Conceptos Básicos de Ciberseguridad',
                'content' => json_encode([
                    'title' => '1.1 Conceptos Básicos de Ciberseguridad',
                    'text' => 'La ciberseguridad se define como la práctica de proteger los sistemas informáticos, redes, dispositivos y datos frente a accesos no autorizados, daños, interrupciones o cualquier tipo de ataque malicioso. En el contexto de las cooperativas, esto incluye la protección de información financiera de los socios, datos personales, sistemas de gestión y plataformas digitales de servicios.'
                ]),
                'order' => 2,
            ],
            [
                'module_id' => $moduleIds[0],
                'type' => 'reading',
                'title' => 'Los Tres Pilares de la Seguridad',
                'content' => json_encode([
                    'title' => 'Los Tres Pilares de la Seguridad',
                    'text' => 'Los tres pilares fundamentales de la seguridad de la información son: Confidencialidad (asegurar que la información solo sea accesible para personas autorizadas), Integridad (garantizar que los datos no sean alterados de forma no autorizada) y Disponibilidad (asegurar que los sistemas y datos estén disponibles cuando se necesiten).'
                ]),
                'order' => 3,
            ],
            [
                'module_id' => $moduleIds[0],
                'type' => 'image',
                'title' => 'Imagen Hacker',
                'content' => json_encode([
                    'img' => '/hacker.jpg',
                    'alt' => 'Representación de ciberseguridad'
                ]),
                'order' => 4,
            ],
            [
                'module_id' => $moduleIds[0],
                'type' => 'video',
                'title' => 'Fundamentos de Ciberseguridad',
                'content' => json_encode([
                    'title' => 'Fundamentos de Ciberseguridad',
                    'src' => '/videos/curso3unidad1video1.mp4',
                    'poster' => '/videos/curso3unidad1img1.png'
                ]),
                'order' => 5,
            ],
        ];

        // Componentes del Módulo 2 - Importancia en Cooperativas
        $components2 = [
            [
                'module_id' => $moduleIds[1],
                'type' => 'reading',
                'title' => '1.2 Importancia en el Sector Cooperativo',
                'content' => json_encode([
                    'title' => '1.2 Importancia en el Sector Cooperativo',
                    'text' => 'Las cooperativas financieras manejan información altamente sensible de sus socios, incluyendo datos personales, información financiera, historial crediticio y transacciones. Esta información es extremadamente valiosa para los ciberdelincuentes, quienes pueden utilizarla para cometer fraudes, robo de identidad o extorsión.'
                ]),
                'order' => 1,
            ],
            [
                'module_id' => $moduleIds[1],
                'type' => 'reading',
                'title' => 'La Confianza como Activo',
                'content' => json_encode([
                    'title' => 'La Confianza como Activo',
                    'text' => 'La confianza es el activo más importante de una cooperativa. Los socios depositan su confianza en la institución para proteger sus ahorros e información personal. Un incidente de ciberseguridad puede dañar irreparablemente esta confianza, resultando en pérdida de socios, problemas regulatorios y daño reputacional.'
                ]),
                'order' => 2,
            ],
            [
                'module_id' => $moduleIds[1],
                'type' => 'reading',
                'title' => 'Características del Sector Cooperativo',
                'content' => json_encode([
                    'title' => 'Características del Sector Cooperativo',
                    'text' => 'Las cooperativas procesan miles de transacciones diarias a través de canales digitales • Manejan información personal y financiera de cientos o miles de socios • Utilizan sistemas interconectados que pueden ser vulnerables a ataques • Son objetivos atractivos para ciberdelincuentes debido a los recursos financieros que manejan • Deben cumplir con regulaciones estrictas de protección de datos'
                ]),
                'order' => 3,
            ],
            [
                'module_id' => $moduleIds[1],
                'type' => 'image',
                'title' => 'Imagen Banca Móvil',
                'content' => json_encode([
                    'img' => '/bancaMovil.jpg',
                    'alt' => 'Banca móvil y cooperativas'
                ]),
                'order' => 4,
            ],
        ];

        // Componentes del Módulo 3 - Principales Amenazas
        $components3 = [
            [
                'module_id' => $moduleIds[2],
                'type' => 'reading',
                'title' => '1.3 Principales Amenazas Actuales',
                'content' => json_encode([
                    'title' => '1.3 Principales Amenazas Actuales',
                    'text' => 'El panorama de amenazas cibernéticas evoluciona constantemente. Las cooperativas enfrentan diversos tipos de ataques que pueden comprometer su seguridad y la de sus socios. Es fundamental conocer estas amenazas para poder defenderse adecuadamente.'
                ]),
                'order' => 1,
            ],
            [
                'module_id' => $moduleIds[2],
                'type' => 'reading',
                'title' => 'Tipos de Amenazas',
                'content' => json_encode([
                    'title' => 'Tipos de Amenazas',
                    'text' => 'Phishing: Correos electrónicos o mensajes fraudulentos que intentan obtener credenciales de acceso • Ransomware: Software malicioso que cifra archivos y exige un rescate para liberarlos • Ataques a la banca móvil: Intentos de comprometer aplicaciones móviles bancarias • Fraude en transacciones: Manipulación de sistemas de pago para realizar transferencias no autorizadas • Ingeniería social: Manipulación psicológica de empleados para obtener acceso a sistemas • Ataques DDoS: Saturación de servidores para interrumpir servicios digitales'
                ]),
                'order' => 2,
            ],
            [
                'module_id' => $moduleIds[2],
                'type' => 'image',
                'title' => 'Imagen Phishing Cooperativas',
                'content' => json_encode([
                    'img' => '/phishingCoop.jpg',
                    'alt' => 'Phishing dirigido a cooperativas'
                ]),
                'order' => 3,
            ],
            [
                'module_id' => $moduleIds[2],
                'type' => 'video',
                'title' => 'Tipos de Ataques Comunes',
                'content' => json_encode([
                    'title' => 'Tipos de Ataques Comunes',
                    'src' => '/videos/curso3unidad1video2.mp4',
                    'poster' => '/videos/curso3unidad1img2.png'
                ]),
                'order' => 4,
            ],
            [
                'module_id' => $moduleIds[2],
                'type' => 'reading',
                'title' => 'Estadísticas de Ciberataques',
                'content' => json_encode([
                    'title' => 'Estadísticas de Ciberataques',
                    'text' => 'Según estudios recientes, el 78% de las instituciones financieras ha experimentado al menos un intento de ciberataque en el último año. Las cooperativas, al igual que los bancos tradicionales, deben implementar medidas robustas de ciberseguridad para proteger a sus socios y cumplir con las regulaciones aplicables.'
                ]),
                'order' => 5,
            ],
        ];

        // Insertar todos los componentes
        $allComponents = array_merge($components1, $components2, $components3);
        foreach ($allComponents as $component) {
            DB::table('module_components')->insert([
                'module_id' => $component['module_id'],
                'type' => $component['type'],
                'title' => $component['title'],
                'content' => $component['content'],
                'order' => $component['order'],
                'is_mandatory' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear quiz para la unidad
        DB::table('course_quizzes')->insert([
            'unit_id' => $unit1,
            'questions' => json_encode([
                [
                    'question' => '¿Qué busca proteger la ciberseguridad en una cooperativa?',
                    'options' => [
                        'Solo los sistemas físicos',
                        'La información y los sistemas frente a accesos no autorizados',
                        'El acceso a redes sociales'
                    ],
                    'answer' => 2,
                ],
                [
                    'question' => '¿Qué representa el principio de "disponibilidad" en la seguridad de la información?',
                    'options' => [
                        'Que los datos estén cifrados',
                        'Que los datos estén siempre accesibles cuando se necesiten',
                        'Que los datos no se compartan'
                    ],
                    'answer' => 2,
                ],
                [
                    'question' => '¿Por qué son las cooperativas objetivos atractivos para ciberdelincuentes?',
                    'options' => [
                        'Porque no tienen sistemas de seguridad',
                        'Porque manejan información financiera valiosa y recursos económicos',
                        'Porque son empresas pequeñas'
                    ],
                    'answer' => 2,
                ],
                [
                    'question' => '¿Cuál es el activo más importante de una cooperativa en términos de ciberseguridad?',
                    'options' => [
                        'Los equipos informáticos',
                        'La confianza de los socios',
                        'El dinero en efectivo'
                    ],
                    'answer' => 2,
                ],
            ]),
            'order_index' => 999,
            'passing_score' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('✅ Contenido del Curso 3 (Ciberseguridad en Cooperativas) migrado exitosamente');
        $this->command->info("   • Unidad creada: {$unit1}");
        $this->command->info("   • Módulos creados: " . implode(', ', $moduleIds));
        $this->command->info('   • Componentes con videos y quiz añadidos');
    }
}