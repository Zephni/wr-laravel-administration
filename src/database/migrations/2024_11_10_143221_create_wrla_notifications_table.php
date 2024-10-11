<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wrla_notifications', function (Blueprint $table) {
            // Id
            $table->id();

            // Type
            $table->string('type');

            // User ID (foreign key to users table)
            $table->string('user_id');

            // Data
            $table->json('data');

            // Read at
            $table->timestamp('read_at')->nullable();

            // Created at and updated at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wrla_notifications');
    }
};
