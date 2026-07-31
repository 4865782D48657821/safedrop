<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained('users');
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('summary', 500);
            $table->text('description')->nullable();
            $table->string('game');
            $table->string('project_type');
            $table->json('categories')->nullable();
            $table->json('tags')->nullable();
            $table->string('language', 12)->default('en');
            $table->string('license')->nullable();
            $table->string('publication_status')->default('draft');
            $table->string('moderation_status')->default('pending');
            $table->string('age_rating')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
