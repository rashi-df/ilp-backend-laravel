<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('thumbnail')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('avatar')->nullable()->change();
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->text('thumbnail')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->change();
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->change();
        });
    }
};
