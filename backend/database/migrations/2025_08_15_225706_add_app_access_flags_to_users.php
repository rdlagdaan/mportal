<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'micro_enabled')) {
                $table->boolean('micro_enabled')->default(false)->index();
            }
            if (!Schema::hasColumn('users', 'lrwsis_enabled')) {
                $table->boolean('lrwsis_enabled')->default(false)->index();
            }
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'micro_enabled')) {
                $table->dropColumn('micro_enabled');
            }
            if (Schema::hasColumn('users', 'lrwsis_enabled')) {
                $table->dropColumn('lrwsis_enabled');
            }
        });
    }
};
