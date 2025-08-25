<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Para MySQL, necesitamos usar una consulta directa para modificar el enum
        DB::statement("ALTER TABLE module_components MODIFY COLUMN type ENUM('banner', 'video', 'reading', 'image', 'document', 'audio', 'interactive', 'quiz') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el enum al estado anterior (sin quiz)
        DB::statement("ALTER TABLE module_components MODIFY COLUMN type ENUM('banner', 'video', 'reading', 'image', 'document', 'audio', 'interactive') NOT NULL");
    }
};
