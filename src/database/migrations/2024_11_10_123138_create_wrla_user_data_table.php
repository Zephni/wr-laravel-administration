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
        Schema::create('wrla_user_data', function (Blueprint $table): void {
            // Id
            $table->id();

            // User ID (foreign key to users table)
            $table->bigInteger('user_id');

            // Avatar
            $table->string('avatar')->nullable()->default(null);

            // Add permissions JSON column
            $table->json('permissions')->nullable()->default(null);

            // Add settings JSON column
            $table->json('settings')->nullable()->default(null);

            // Add data JSON column
            $table->json('data')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wrla_user_data');
    }
};
