<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_id', 'creator_id']);
        });

        DB::statement('ALTER TABLE creator_follows ADD CONSTRAINT creator_follows_no_self_follow CHECK (follower_id <> creator_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_follows');
    }
};
