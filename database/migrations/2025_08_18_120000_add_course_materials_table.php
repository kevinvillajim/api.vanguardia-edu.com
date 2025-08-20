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
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['pdf', 'document', 'video', 'link', 'image']);
            $table->string('file_url');
            $table->string('file_name')->nullable();
            $table->integer('file_size')->nullable(); // en bytes
            $table->string('mime_type')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Agregar campos adicionales a module_components para contenido rico
        Schema::table('module_components', function (Blueprint $table) {
            $table->longText('rich_content')->nullable()->after('content'); // HTML/Markdown rico
            $table->json('metadata')->nullable()->after('rich_content'); // Metadatos específicos del tipo
            $table->string('thumbnail_url')->nullable()->after('metadata'); // URL de miniatura/preview
            $table->integer('estimated_duration')->nullable()->after('thumbnail_url'); // Duración estimada en minutos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_materials');
        
        Schema::table('module_components', function (Blueprint $table) {
            $table->dropColumn(['rich_content', 'metadata', 'thumbnail_url', 'estimated_duration']);
        });
    }
};