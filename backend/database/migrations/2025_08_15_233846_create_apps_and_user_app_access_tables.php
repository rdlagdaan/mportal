<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();             // LRWSIS, OPENU, MICRO
            $table->string('name', 100);
            $table->string('session_cookie', 64)->nullable(); // e.g., lrwsis_session
            $table->string('session_path', 64)->nullable();   // e.g., /lrwsis
            $table->timestamps();
        });

        Schema::create('user_app_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'app_id']);      // user can appear once per app
            $table->index(['app_id', 'is_enabled']);    // fast filter for active users per app
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_app_access');
        Schema::dropIfExists('apps');
    }
};
