<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_completed_lessons', function (Blueprint $table) {
            $table->foreignId('user_progress_id')->constrained('user_progress')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_progress_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_completed_lessons');
    }
};
