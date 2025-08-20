<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('course_enrollments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['virtual', 'complete']);
            $table->string('certificate_number')->unique();
            $table->dateTime('issued_at');
            $table->decimal('final_score', 5, 2)->nullable();
            $table->decimal('course_progress', 5, 2); // Porcentaje de completitud
            $table->decimal('interactive_average', 5, 2)->nullable(); // Promedio de cuestionarios
            $table->decimal('activities_average', 5, 2)->nullable(); // Promedio de actividades
            $table->json('metadata')->nullable(); // Información adicional
            $table->string('file_url')->nullable(); // URL del PDF generado
            $table->boolean('is_valid')->default(true);
            $table->text('invalidation_reason')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'type']);
            $table->index(['course_id', 'type']);
            $table->index('certificate_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
