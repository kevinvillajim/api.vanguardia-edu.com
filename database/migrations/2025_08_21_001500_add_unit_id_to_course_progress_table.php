<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_progress', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('course_id')->constrained('course_units')->onDelete('cascade');
            $table->index(['unit_id', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::table('course_progress', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};