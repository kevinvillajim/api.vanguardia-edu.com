<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('course_activities')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('course_enrollments')->onDelete('cascade');
            $table->enum('status', ['pending', 'submitted', 'graded', 'returned']);
            $table->text('submission_content')->nullable();
            $table->json('attachments')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users');
            $table->dateTime('graded_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamps();

            $table->unique(['activity_id', 'student_id']);
            $table->index(['student_id', 'status']);
            $table->index(['enrollment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_activities');
    }
};
