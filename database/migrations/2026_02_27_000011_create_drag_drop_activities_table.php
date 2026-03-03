<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drag_drop_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['ordering', 'matching'])->default('ordering');
            $table->jsonb('items')->default('[]');
            $table->text('instructions')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drag_drop_activities');
    }
};
