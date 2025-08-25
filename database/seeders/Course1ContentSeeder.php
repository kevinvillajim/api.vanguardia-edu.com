<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Course\Models\Course;
use Illuminate\Support\Facades\DB;

class Course1ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar el curso de Fundamentos de Protección de Datos
        $course = Course::where('slug', 'fundamentos-proteccion-datos')->first();
        
        if (!$course) {
            $this->command->error('Curso "Fundamentos de Protección de Datos" no encontrado');
            return;
        }

        // Crear Unidad 1
        $unit1 = DB::table('course_units')->insertGetId([
            'course_id' => $course->id,
            'title' => 'Unidad 1: Definiciones Importantes',
            'description' => 'Aprende los conceptos fundamentales sobre datos personales y sensibles',
            'banner_image' => '/c1Banner1.jpg',
            'order_index' => 1,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear módulos para Unidad 1
        $module1 = DB::table('course_modules')->insertGetId([
            'course_id' => $course->id,
            'unit_id' => $unit1,
            'title' => '1.1 Datos Personales',
            'description' => 'Concepto y ejemplos de datos personales',
            'order_index' => 1,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $module2 = DB::table('course_modules')->insertGetId([
            'course_id' => $course->id,
            'unit_id' => $unit1,
            'title' => '1.2 Datos Sensibles',
            'description' => 'Concepto y tipos de datos sensibles',
            'order_index' => 2,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Componentes del Módulo 1.1 - Datos Personales
        $components1 = [
            [
                'module_id' => $module1,
                'type' => 'banner',
                'title' => 'Banner Unidad 1',
                'content' => json_encode([
                    'img' => '/c1Banner1.jpg',
                    'title' => 'Unidad 1: Definiciones Importantes'
                ]),
                'order' => 1,
            ],
            [
                'module_id' => $module1,
                'type' => 'reading',
                'title' => '1.1 Datos Personales',
                'content' => json_encode([
                    'title' => '1.1 Datos Personales',
                    'text' => 'Los datos personales son cualquier información que se refiere a una persona física identificada o identificable. Esto incluye datos como el nombre, dirección, número de identificación, datos de ubicación, dirección de correo electrónico, número de teléfono, así como información más sensible como datos de salud, origen étnico, opiniones políticas, creencias religiosas y datos biométricos. Por ejemplo, el número de la seguridad social de una persona, su dirección IP (en ciertos contextos), o su historial médico son considerados datos personales porque permiten identificar directa o indirectamente a una persona. La protección de estos datos es fundamental para preservar la privacidad y los derechos de los individuos.'
                ]),
                'order' => 2,
            ],
            [
                'module_id' => $module1,
                'type' => 'image',
                'title' => 'Imagen Datos Personales',
                'content' => json_encode([
                    'img' => '/dp1.png',
                    'alt' => 'Representación visual de datos personales'
                ]),
                'order' => 3,
            ],
        ];

        // Componentes del Módulo 1.2 - Datos Sensibles
        $components2 = [
            [
                'module_id' => $module2,
                'type' => 'reading',
                'title' => '1.2 Datos Sensibles',
                'content' => json_encode([
                    'title' => '1.2 Datos Sensibles',
                    'text' => 'Los datos sensibles son un tipo específico de datos personales que revelan información particularmente delicada y cuyo tratamiento inadecuado puede afectar significativamente los derechos y libertades de las personas. Estos datos incluyen aspectos como el origen racial o étnico, opiniones políticas, creencias religiosas o filosóficas, afiliación sindical, datos genéticos, datos biométricos destinados a identificar de manera unívoca a una persona física, datos relativos a la salud, vida sexual u orientación sexual de una persona. Debido a su naturaleza, los datos sensibles están sujetos a un nivel más alto de protección y regulaciones estrictas para asegurar su confidencialidad y uso adecuado.'
                ]),
                'order' => 1,
            ],
            [
                'module_id' => $module2,
                'type' => 'image',
                'title' => 'Imagen Datos Sensibles',
                'content' => json_encode([
                    'img' => '/ds1.png',
                    'alt' => 'Representación visual de datos sensibles'
                ]),
                'order' => 2,
            ],
        ];

        // Insertar componentes
        foreach (array_merge($components1, $components2) as $component) {
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
                    'question' => '¿Qué es un dato personal?',
                    'options' => [
                        'Información sobre empresas',
                        'Cualquier información que se refiere a una persona física identificada o identificable',
                        'Información sobre objetos inanimados',
                    ],
                    'answer' => 2,
                ],
                [
                    'question' => '¿Qué son datos sensibles?',
                    'options' => [
                        'Datos sobre las opiniones políticas de una persona',
                        'Datos sobre el clima',
                        'Datos sobre las transacciones comerciales',
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => '¿Cuál de los siguientes ejemplos sería considerado un dato personal?',
                    'options' => [
                        'La dirección IP de un dispositivo en ciertos contextos',
                        'El número de registro de un automóvil',
                        'Las estadísticas de producción de una fábrica',
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => '¿Cuál es la característica principal que diferencia a los datos sensibles de otros datos personales?',
                    'options' => [
                        'Incluyen información financiera únicamente.',
                        'Son datos que pueden ser compartidos libremente sin restricciones.',
                        'Su tratamiento inadecuado puede afectar significativamente los derechos y libertades de las personas.',
                    ],
                    'answer' => 3,
                ],
            ]),
            'order_index' => 999,
            'passing_score' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('✅ Contenido del Curso 1 (Fundamentos de Protección de Datos) migrado exitosamente');
        $this->command->info("   • Unidad creada: {$unit1}");
        $this->command->info("   • Módulos creados: {$module1}, {$module2}");
        $this->command->info('   • Componentes y quiz añadidos');
    }
}