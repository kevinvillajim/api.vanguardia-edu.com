<?php

namespace Database\Seeders;

use App\Domain\Course\Models\CourseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Programación',
                'description' => 'Cursos de desarrollo de software, lenguajes de programación y tecnologías web',
                'icon' => 'code'
            ],
            [
                'name' => 'Diseño',
                'description' => 'Diseño gráfico, UX/UI, diseño web y multimedia',
                'icon' => 'palette'
            ],
            [
                'name' => 'Marketing',
                'description' => 'Marketing digital, redes sociales y estrategias de ventas',
                'icon' => 'trending-up'
            ],
            [
                'name' => 'Negocios',
                'description' => 'Emprendimiento, gestión empresarial y finanzas',
                'icon' => 'briefcase'
            ],
            [
                'name' => 'Ciencias',
                'description' => 'Matemáticas, física, química y ciencias naturales',
                'icon' => 'beaker'
            ],
            [
                'name' => 'Idiomas',
                'description' => 'Aprendizaje de idiomas extranjeros',
                'icon' => 'translate'
            ],
            [
                'name' => 'Arte y Música',
                'description' => 'Bellas artes, música y expresión creativa',
                'icon' => 'music'
            ],
            [
                'name' => 'Salud y Bienestar',
                'description' => 'Fitness, nutrición y bienestar personal',
                'icon' => 'heart'
            ]
        ];

        foreach ($categories as $categoryData) {
            CourseCategory::create($categoryData);
        }
    }
}
