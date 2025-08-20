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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('banner_image')->nullable();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('duration_hours')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('enrollment_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('course_categories')->onDelete('set null');
            $table->index(['slug', 'is_published']);
            $table->index('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
