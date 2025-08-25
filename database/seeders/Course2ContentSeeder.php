<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Course\Models\Course;
use Illuminate\Support\Facades\DB;

class Course2ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar el curso de Seguridad y Fraudes Financieros
        $course = Course::where('slug', 'introduccion-seguridad-fraudes-financieros')->first();
        
        if (!$course) {
            $this->command->error('Curso "Introducción a la Seguridad y Fraudes Financieros" no encontrado');
            return;
        }

        // Crear Unidad 1
        $unit1 = DB::table('course_units')->insertGetId([
            'course_id' => $course->id,
            'title' => 'Unidad 1: Principales Tipos de Fraudes en Canales Digitales Financieros',
            'description' => 'Conoce los principales ataques de ciberdelincuentes en el ámbito financiero',
            'banner_image' => '/c2Banner1.jpg',
            'order_index' => 1,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear módulos para Unidad 1
        $modules = [
            [
                'title' => 'Introducción a la Seguridad Financiera',
                'description' => 'Introducción general al curso',
                'order' => 1,
            ],
            [
                'title' => 'Phishing',
                'description' => 'Técnicas de phishing y cómo prevenirlas',
                'order' => 2,
            ],
            [
                'title' => 'Malware',
                'description' => 'Tipos de malware y protección',
                'order' => 3,
            ],
            [
                'title' => 'Robo de Identidad',
                'description' => 'Prevención del robo de identidad',
                'order' => 4,
            ],
            [
                'title' => 'Fraude en Comercio Electrónico',
                'description' => 'Fraudes en e-commerce y prevención',
                'order' => 5,
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

        // Componentes del Módulo 1 - Introducción
        $components1 = [
            [
                'module_id' => $moduleIds[0],
                'type' => 'banner',
                'title' => 'Banner Unidad 1',
                'content' => json_encode([
                    'img' => '/c2Banner1.jpg',
                    'title' => 'Unidad 1: Principales Tipos de Fraudes en Canales Digitales Financieros'
                ]),
                'order' => 1,
            ],
            [
                'module_id' => $moduleIds[0],
                'type' => 'reading',
                'title' => 'Introducción a la Seguridad Financiera',
                'content' => json_encode([
                    'title' => 'Introducción a la Seguridad Financiera',
                    'text' => 'En la era digital, la seguridad y la prevención del fraude se han vuelto más importantes que nunca. Los canales digitales, como la banca en línea, las aplicaciones de pago móvil y las plataformas de comercio electrónico, han revolucionado la forma en que interactuamos con el dinero, brindándonos comodidad y accesibilidad sin precedentes. Sin embargo, estas innovaciones también han creado nuevas oportunidades para que los ciberdelincuentes perpetren fraudes.'
                ]),
                'order' => 2,
            ],
            [
                'module_id' => $moduleIds[0],
                'type' => 'reading',
                'title' => 'Objetivos del Curso',
                'content' => json_encode([
                    'title' => 'Objetivos del Curso',
                    'text' => 'Este curso, "Introducción a la seguridad y fraudes financieros", está diseñado para proporcionar una comprensión integral de los principales ataques de ciberdelincuentes y estafadores. Exploraremos los diferentes tipos de fraudes, desde el phishing y el malware hasta el robo de identidad y el fraude en el comercio electrónico. Además, aprenderemos métodos efectivos de prevención y estrategias para el manejo seguro de transacciones, con el fin de proteger nuestros activos financieros en el entorno digital. Este curso ofrecerá las herramientas necesarias para identificar señales de fraude y adoptar medidas de seguridad robustas. Al finalizar, estarás mejor preparado para enfrentar los desafíos de seguridad en los canales digitales financieros y contribuir a un entorno digital más seguro.'
                ]),
                'order' => 3,
            ],
            [
                'module_id' => $moduleIds[0],
                'type' => 'image',
                'title' => 'Imagen Fraudes Financieros',
                'content' => json_encode([
                    'img' => '/ff.jpeg',
                    'alt' => 'Representación de fraudes financieros'
                ]),
                'order' => 4,
            ],
        ];

        // Componentes del Módulo 2 - Phishing
        $components2 = [
            [
                'module_id' => $moduleIds[1],
                'type' => 'reading',
                'title' => 'Phishing',
                'content' => json_encode([
                    'title' => 'Phishing',
                    'text' => 'El phishing es una técnica utilizada por los ciberdelincuentes para engañar a los usuarios y robar información personal y financiera confidencial. Esto se hace mediante correos electrónicos, mensajes de texto, sitios web falsos o inclusive mensajes de whatsapp o llamadas telefónicas, haciendose pasar por entidades legítimas. Los usuarios pueden recibir un correo electrónico que parece provenir de su banco, solicitando que verifiquen su información de cuenta. Al hacer clic en el enlace y proporcionar la información solicitada, los datos son enviados directamente a los delincuentes.'
                ]),
                'order' => 1,
            ],
            [
                'module_id' => $moduleIds[1],
                'type' => 'video',
                'title' => 'Que es Phishing?',
                'content' => json_encode([
                    'title' => 'Que es Phishing?',
                    'src' => '/videos/curso2unidad1video.mp4',
                    'poster' => '/videos/curso2unidad1img.png'
                ]),
                'order' => 2,
            ],
        ];

        // Componentes del Módulo 3 - Malware
        $components3 = [
            [
                'module_id' => $moduleIds[2],
                'type' => 'reading',
                'title' => 'Malware',
                'content' => json_encode([
                    'title' => 'Malware',
                    'text' => 'El malware se refiere a cualquier software diseñado para dañar, explotar o infiltrarse en dispositivos. Los tipos comunes de malware incluyen virus, troyanos, gusanos y ransomware. Una vez que el malware infecta un dispositivo, puede robar datos, interceptar transacciones o incluso tomar el control del dispositivo. Es crucial contar con software antivirus actualizado y estar alerta a las descargas sospechosas para evitar infecciones, es de suma importancia al descargar programas hacerlo de los sitios oficiales, y evitar a toda costa programas, juegos o cualquer otro tipo de software pirata o crackeado ya que esta es una de las principales vias de infección'
                ]),
                'order' => 1,
            ],
            [
                'module_id' => $moduleIds[2],
                'type' => 'video',
                'title' => 'Que es malware?',
                'content' => json_encode([
                    'title' => 'Que es malware?',
                    'src' => '/videos/curso2unidad1video2.mp4',
                    'poster' => '/videos/curso2unidad1img2.png'
                ]),
                'order' => 2,
            ],
        ];

        // Componentes del Módulo 4 - Robo de Identidad
        $components4 = [
            [
                'module_id' => $moduleIds[3],
                'type' => 'reading',
                'title' => 'Robo de Identidad',
                'content' => json_encode([
                    'title' => 'Robo de Identidad',
                    'text' => 'El robo de identidad ocurre cuando los ciberdelincuentes usan ilegalmente la información personal de alguien para obtener acceso a cuentas financieras o solicitar servicios en su nombre. Esto puede incluir el uso de números de seguro social, cédula de identidad, números de tarjetas de crédito y otra información personal para realizar compras no autorizadas, abrir nuevas cuentas de crédito o cometer otros tipos de fraude financiero.'
                ]),
                'order' => 1,
            ],
            [
                'module_id' => $moduleIds[3],
                'type' => 'video',
                'title' => 'Como evitar el Robo de tu Información?',
                'content' => json_encode([
                    'title' => 'Como evitar el Robo de tu Información?',
                    'src' => '/videos/curso2unidad1video3.mp4',
                    'poster' => '/videos/curso2unidad1img3.png'
                ]),
                'order' => 2,
            ],
        ];

        // Componentes del Módulo 5 - Fraude en E-commerce
        $components5 = [
            [
                'module_id' => $moduleIds[4],
                'type' => 'reading',
                'title' => 'Fraude en Comercio Electrónico',
                'content' => json_encode([
                    'title' => 'Fraude en Comercio Electrónico',
                    'text' => 'El fraude en el comercio electrónico puede tomar varias formas, incluyendo el uso de tarjetas de crédito robadas, la creación de cuentas falsas o la manipulación de reseñas de productos para engañar a los consumidores. Este tipo de fraude no solo afecta a los consumidores, sino también a los comerciantes, que pueden enfrentar pérdidas financieras significativas y daños a su reputación.'
                ]),
                'order' => 1,
            ],
            [
                'module_id' => $moduleIds[4],
                'type' => 'image',
                'title' => 'Imagen Fraude E-commerce',
                'content' => json_encode([
                    'img' => '/ff1.jpg',
                    'alt' => 'Fraude en comercio electrónico'
                ]),
                'order' => 2,
            ],
        ];

        // Insertar todos los componentes
        $allComponents = array_merge($components1, $components2, $components3, $components4, $components5);
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
                    'question' => '¿Cuál es el principal objetivo del phishing?',
                    'options' => [
                        'Robar información personal y financiera',
                        'Destruir datos en el dispositivo',
                        'Crear cuentas falsas',
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => '¿Cual de estas opciones es también conocido como virus?',
                    'options' => ['Phishing', 'Robo de Identidad', 'Malware'],
                    'answer' => 3,
                ],
                [
                    'question' => '¿Cuál es una forma efectiva de prevenir el malware?',
                    'options' => [
                        'Usar software antivirus actualizado',
                        'Ignorar las actualizaciones del sistema',
                        'Descargar software de sitios no oficiales',
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => '¿Qué información pueden usar los ciberdelincuentes para el robo de identidad?',
                    'options' => [
                        'Número de cédula',
                        'Estadísticas de producción',
                        'Información meteorológica',
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => '¿Cuál de las siguientes opciones NO es una forma de fraude en el comercio electrónico?',
                    'options' => [
                        'Uso de tarjetas de crédito robadas',
                        'Manipulación de reseñas de productos',
                        'Verificar transacciones bancarias',
                    ],
                    'answer' => 3,
                ],
                [
                    'question' => '¿Qué medida ayuda a prevenir el fraude en el comercio electrónico?',
                    'options' => [
                        'Utilizar conexiones inseguras',
                        'Revisar reseñas del vendedor e investigar',
                        'Compartir información personal en sitios no seguros',
                    ],
                    'answer' => 2,
                ],
            ]),
            'order_index' => 999,
            'passing_score' => 70,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('✅ Contenido del Curso 2 (Introducción a la Seguridad y Fraudes Financieros) migrado exitosamente');
        $this->command->info("   • Unidad creada: {$unit1}");
        $this->command->info("   • Módulos creados: " . implode(', ', $moduleIds));
        $this->command->info('   • Componentes con videos y quiz añadidos');
    }
}