<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_locale', 12)->nullable();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->json('translations')->nullable();
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->json('translation_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', fn (Blueprint $table) => $table->dropColumn('translation_data'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('translations'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('preferred_locale'));
    }
};
