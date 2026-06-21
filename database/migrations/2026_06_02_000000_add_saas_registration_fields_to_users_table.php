<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('email');
            $table->string('store_url')->nullable()->unique()->after('company_name');
            $table->string('phone', 30)->nullable()->after('store_url');
            $table->unsignedBigInteger('plan')->default(1)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['store_url']);
            $table->dropColumn(['company_name', 'store_url', 'phone', 'plan']);
        });
    }
};
