<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('course_enrollments')->onDelete('cascade');
            $table->integer('attempt_number')->default(1);
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->json('answers'); // Respuestas del estudiante
            $table->json('question_scores')->nullable(); // Puntaje por pregunta
            $table->enum('status', ['in_progress', 'completed', 'abandoned']);
            $table->integer('time_spent')->nullable(); // En segundos
            $table->timestamps();

            $table->index(['student_id', 'quiz_id']);
            $table->index(['enrollment_id', 'status']);
            $table->unique(['quiz_id', 'student_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
