<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('notify_new_projects')->default(true);
            $table->boolean('notify_new_releases')->default(true);
            $table->boolean('notify_livestreams')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'creator_id']);
        });

        DB::statement('ALTER TABLE creator_notification_preferences ADD CONSTRAINT creator_notification_preferences_no_self_creator CHECK (user_id <> creator_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_notification_preferences');
    }
};
