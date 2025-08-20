<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('course_enrollments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->nullable()->constrained('course_modules')->onDelete('cascade');
            $table->foreignId('component_id')->nullable()->constrained('module_components')->onDelete('cascade');
            $table->enum('type', ['module', 'component', 'quiz', 'activity']);
            $table->foreignId('reference_id'); // ID del elemento completado
            $table->boolean('is_completed')->default(false);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('time_spent')->default(0); // En segundos
            $table->decimal('score', 5, 2)->nullable(); // Para quizzes y actividades
            $table->json('metadata')->nullable(); // Información adicional
            $table->timestamps();

            $table->unique(['enrollment_id', 'type', 'reference_id']);
            $table->index(['student_id', 'course_id']);
            $table->index(['enrollment_id', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_progress');
    }
};
