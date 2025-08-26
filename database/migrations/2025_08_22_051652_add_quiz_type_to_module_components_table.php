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
        // SQLite doesn't support MODIFY COLUMN, so we skip this for SQLite
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE module_components MODIFY COLUMN type ENUM('banner', 'video', 'reading', 'image', 'document', 'audio', 'interactive', 'quiz') NOT NULL");
        }
        // For SQLite, the column is already text and can accept 'quiz' values
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite doesn't support MODIFY COLUMN, so we skip this for SQLite
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE module_components MODIFY COLUMN type ENUM('banner', 'video', 'reading', 'image', 'document', 'audio', 'interactive') NOT NULL");
        }
    }
};
