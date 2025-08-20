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
        Schema::create('lesson_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_id');
            $table->enum('content_type', ['video', 'text', 'image', 'quiz', 'activity', 'file', 'code']);
            $table->json('content_data');
            $table->integer('order_index')->default(0);
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('course_lessons')->onDelete('cascade');
            $table->index(['lesson_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_contents');
    }
};
