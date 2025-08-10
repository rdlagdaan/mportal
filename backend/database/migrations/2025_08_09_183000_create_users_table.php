<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->nullable();
            $table->string('email', 150)->unique();
            $table->string('mobile_number', 30)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password'); // bcrypt hashed
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
