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
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->foreign('module_id')->references('id')->on('course_modules')->onDelete('cascade');
            $table->index(['module_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_lessons');
    }
};
