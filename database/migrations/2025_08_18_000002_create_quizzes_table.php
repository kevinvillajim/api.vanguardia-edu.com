<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('time_limit')->nullable(); // En minutos
            $table->integer('max_attempts')->default(1);
            $table->decimal('passing_score', 5, 2)->default(70); // Porcentaje mínimo para aprobar
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('is_mandatory')->default(true);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['module_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
