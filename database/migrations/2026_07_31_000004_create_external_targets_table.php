<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->text('original_url');
            $table->text('normalized_url')->nullable();
            $table->json('redirect_chain')->nullable();
            $table->string('target_domain')->index();
            $table->string('domain_status')->default('new')->index();
            $table->string('target_type')->default('project_page');
            $table->timestamp('last_checked_at')->nullable();
            $table->string('reachability_status')->default('unchecked');
            $table->string('trust_status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_targets');
    }
};
