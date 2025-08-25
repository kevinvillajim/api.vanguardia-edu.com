<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('course_units')->onDelete('cascade');
            $table->json('questions'); // Array de preguntas con opciones y respuestas
            $table->integer('order_index')->default(999); // Al final de la unidad
            $table->integer('passing_score')->default(70); // Porcentaje mínimo para aprobar
            $table->timestamps();
            
            $table->index('unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_quizzes');
    }
};
