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
        Schema::create('progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('progress', 4, 2);
            $table->boolean('completed')->default(false);
            $table->date('finishDate')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->boolean('certificate')->default(false);
            $table->boolean('attempted')->nullable();
            $table->timestamps();
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress');
    }
};
