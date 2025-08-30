<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_clones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_course_id')->constrained('courses');
            $table->foreignId('cloned_course_id')->constrained('courses');
            $table->foreignId('cloned_by')->constrained('users');
            $table->dateTime('cloned_at');
            $table->json('clone_options'); // Qué elementos se clonaron
            $table->timestamps();

            $table->index('original_course_id');
            $table->index('cloned_course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_clones');
    }
};
