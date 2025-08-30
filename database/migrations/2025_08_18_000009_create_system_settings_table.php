<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('category')->default('general');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index('category');
        });

        // Insertar configuraciones iniciales
        DB::table('system_settings')->insert([
            [
                'key' => 'certificate_virtual_threshold',
                'value' => '80',
                'type' => 'integer',
                'category' => 'certificates',
                'description' => 'Porcentaje mínimo de completitud del curso para obtener certificado virtual',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'certificate_complete_threshold',
                'value' => '70',
                'type' => 'integer',
                'category' => 'certificates',
                'description' => 'Promedio mínimo para obtener certificado completo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'grade_weights',
                'value' => json_encode([
                    'interactive' => 50,
                    'activities' => 50,
                ]),
                'type' => 'json',
                'category' => 'grading',
                'description' => 'Pesos para el cálculo del promedio final',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'allow_course_retry',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'courses',
                'description' => 'Permitir reinscripción en cursos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_quiz_attempts',
                'value' => '3',
                'type' => 'integer',
                'category' => 'courses',
                'description' => 'Número máximo de intentos por defecto para cuestionarios',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
