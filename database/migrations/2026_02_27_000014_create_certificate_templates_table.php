<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('subtitle')->default('has successfully completed');
            $table->text('body_text')->default('This is to certify that');
            $table->string('signature_name')->nullable();
            $table->string('signature_title')->nullable();
            $table->string('logo_url')->nullable();
            $table->enum('border_style', ['classic', 'modern', 'ornate', 'minimal'])->default('classic');
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
