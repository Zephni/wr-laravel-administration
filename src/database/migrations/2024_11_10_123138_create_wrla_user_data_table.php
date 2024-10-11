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
        Schema::create('wrla_user_data', function (Blueprint $table) {
            // Id
            $table->id();

            // User ID (foreign key to users table)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Add permissions JSON column
            $table->json('permissions')->nullable();

            // Add settings JSON column
            $table->json('settings')->nullable();

            // Add data JSON column
            $table->json('data')->nullable();
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
