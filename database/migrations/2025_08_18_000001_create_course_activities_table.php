<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['assignment', 'project', 'essay', 'presentation', 'other']);
            $table->decimal('max_score', 5, 2)->default(100);
            $table->decimal('weight', 5, 2)->default(1); // Peso en el promedio final
            $table->dateTime('due_date')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->json('attachments')->nullable(); // Archivos adjuntos
            $table->json('instructions')->nullable(); // Instrucciones detalladas
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'is_active']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_activities');
    }
};
