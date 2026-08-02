<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('signal', 24);
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
        });

        DB::statement("ALTER TABLE project_ratings ADD CONSTRAINT project_ratings_signal_check CHECK (signal IN ('helpful', 'not_helpful'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ratings');
    }
};
