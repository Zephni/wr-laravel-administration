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
        Schema::create('wrla_email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('category')->default('transactional');
            $table->string('alias')->unique();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->json('mappings')->nullable();
            $table->text('description')->nullable();
            $table->boolean('requires_attachment')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wrla_email_templates');
    }
};
