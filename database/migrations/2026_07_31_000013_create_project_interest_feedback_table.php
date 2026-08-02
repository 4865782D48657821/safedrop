<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_interest_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('signal', 32);
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
        });

        DB::statement("ALTER TABLE project_interest_feedback ADD CONSTRAINT project_interest_feedback_signal_check CHECK (signal IN ('not_interested'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('project_interest_feedback');
    }
};
