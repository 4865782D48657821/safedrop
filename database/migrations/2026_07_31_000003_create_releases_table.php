<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->text('changelog')->nullable();
            $table->json('compatibility')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('moderation_status')->default('pending');
            $table->timestamps();

            $table->unique(['project_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
