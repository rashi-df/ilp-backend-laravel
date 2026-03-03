<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('homework_id')->constrained('homework')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('student_name')->nullable();
            $table->string('student_email')->nullable();
            $table->text('content')->nullable();
            $table->string('file_url')->nullable();
            $table->foreignId('assigned_mentor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mentor_name')->nullable();
            $table->enum('status', ['pending', 'under_review', 'reviewed', 'returned'])->default('pending');
            $table->text('feedback')->nullable();
            $table->decimal('grade', 5, 2)->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_submissions');
    }
};
