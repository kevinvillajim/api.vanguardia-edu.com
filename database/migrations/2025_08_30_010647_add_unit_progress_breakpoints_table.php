<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_progress_breakpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('course_enrollments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('course_units')->onDelete('cascade');
            
            // Breakpoints del 25%, 50%, 75%, 100%
            $table->integer('breakpoint_percentage')->comment('25, 50, 75, or 100');
            
            // Progreso de scroll (0-100) cuando se alcanzó el breakpoint
            $table->decimal('scroll_progress', 5, 2)->comment('Scroll progress when breakpoint was reached');
            
            // Progreso de actividades (0-100) cuando se alcanzó el breakpoint
            $table->decimal('activities_progress', 5, 2)->comment('Activities progress when breakpoint was reached');
            
            // Progreso combinado (scroll + actividades)
            $table->decimal('combined_progress', 5, 2)->comment('Combined progress when breakpoint was reached');
            
            // Metadata para información adicional
            $table->json('metadata')->nullable()->comment('Additional data: completed components, etc.');
            
            // Si el progreso inteligente está habilitado para esta unidad
            $table->boolean('intelligent_progress_enabled')->default(false);
            
            // Timestamps cuando se alcanzó el breakpoint
            $table->timestamp('reached_at');
            $table->timestamps();

            // Índices únicos para evitar breakpoints duplicados
            $table->unique(['enrollment_id', 'unit_id', 'breakpoint_percentage'], 'unique_unit_breakpoint');
            $table->index(['student_id', 'course_id', 'unit_id'], 'progress_lookup');
            $table->index(['breakpoint_percentage', 'reached_at'], 'breakpoint_analytics');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_progress_breakpoints');
    }
};
