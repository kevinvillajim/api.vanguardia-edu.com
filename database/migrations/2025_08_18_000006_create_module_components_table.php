<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->onDelete('cascade');
            $table->enum('type', ['banner', 'video', 'reading', 'image', 'document', 'audio', 'interactive']);
            $table->string('title');
            $table->text('content')->nullable(); // Para lecturas o texto
            $table->string('file_url')->nullable(); // Para videos, imágenes, documentos
            $table->json('metadata')->nullable(); // Información adicional (duración, tamaño, etc.)
            $table->integer('duration')->nullable(); // Duración estimada en minutos
            $table->integer('order')->default(0);
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['module_id', 'is_active']);
            $table->index(['module_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_components');
    }
};
