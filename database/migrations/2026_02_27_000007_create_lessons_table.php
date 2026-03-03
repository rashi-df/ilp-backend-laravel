<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->enum('type', ['video', 'quiz', 'flashcard', 'reading', 'assignment'])->default('reading');
            $table->integer('duration')->nullable();
            $table->jsonb('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
