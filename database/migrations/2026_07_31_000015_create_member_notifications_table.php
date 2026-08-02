<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('release_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event_type', 32);
            $table->string('dedupe_key', 160);
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'event_type', 'dedupe_key']);
        });

        DB::statement("ALTER TABLE member_notifications ADD CONSTRAINT member_notifications_event_type_check CHECK (event_type IN ('new_project', 'new_release', 'live_stream'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('member_notifications');
    }
};
