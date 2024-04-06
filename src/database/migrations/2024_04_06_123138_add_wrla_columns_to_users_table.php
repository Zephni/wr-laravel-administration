<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add permissions JSON column
            $table->json('permissions')->nullable()->after('password');

            // Add settings JSON column
            $table->json('settings')->nullable()->after('permissions');

            // Add data JSON column
            $table->json('data')->nullable()->after('settings');

            // Add soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop permissions JSON column
            $table->dropColumn('permissions');

            // Drop settings JSON column
            $table->dropColumn('settings');

            // Drop data JSON column
            $table->dropColumn('data');

            // Drop soft deletes
            $table->dropSoftDeletes();
        });
    }
};
