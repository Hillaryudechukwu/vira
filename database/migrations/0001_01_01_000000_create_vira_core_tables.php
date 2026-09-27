<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('timezone')->default('Europe/London');
            $table->timestamp('mfa_enabled_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('channels', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->string('status', 32)->default('active');
            $table->text('niche');
            $table->text('mission')->nullable();
            $table->text('audience_summary')->nullable();
            $table->string('default_timezone')->default('Europe/London');
            $table->string('operating_mode', 32)->default('guarded');
            $table->jsonb('settings');
            $table->timestamps();
        });

        Schema::create('topic_candidates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->string('title');
            $table->text('angle');
            $table->string('content_pillar', 100);
            $table->string('status', 32)->default('discovered')->index();
            $table->jsonb('component_scores');
            $table->decimal('viral_score', 5, 2)->index();
            $table->decimal('risk_score', 5, 2)->default(0);
            $table->decimal('confidence', 5, 2)->default(0);
            $table->jsonb('score_explanation');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['channel_id', 'status', 'viral_score']);
        });

        Schema::create('content_projects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignUuid('topic_candidate_id')->unique()->constrained('topic_candidates')->restrictOnDelete();
            $table->string('working_title');
            $table->text('objective');
            $table->string('status', 32)->default('drafting')->index();
            $table->unsignedInteger('target_duration_ms')->default(45000);
            $table->string('aspect_ratio')->default('9:16');
            $table->string('language')->default('en-GB');
            $table->unsignedInteger('current_script_version')->default(0);
            $table->timestamps();
        });

        Schema::create('scripts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_project_id')->constrained('content_projects')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('narration');
            $table->unsignedInteger('word_count');
            $table->unsignedInteger('estimated_duration_ms');
            $table->jsonb('structure');
            $table->jsonb('claims');
            $table->jsonb('model_metadata');
            $table->string('prompt_version');
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->unique(['content_project_id', 'version']);
        });

        Schema::create('generation_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_project_id')->constrained('content_projects')->cascadeOnDelete();
            $table->string('provider', 100);
            $table->string('capability', 100);
            $table->string('request_hash', 64)->unique();
            $table->jsonb('input');
            $table->string('provider_request_id', 191)->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->decimal('estimated_cost_gbp', 10, 4)->default(0);
            $table->decimal('actual_cost_gbp', 10, 4)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_project_id')->constrained('content_projects')->cascadeOnDelete();
            $table->foreignUuid('generation_request_id')->nullable()->constrained('generation_requests')->nullOnDelete();
            $table->string('asset_type', 50)->index();
            $table->string('disk')->default('local');
            $table->text('object_key');
            $table->string('mime_type');
            $table->unsignedBigInteger('byte_size')->default(0);
            $table->string('checksum', 64)->index();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->decimal('frame_rate', 6, 3)->nullable();
            $table->jsonb('provenance');
            $table->string('rights_status', 32)->default('pending_review');
            $table->timestamps();
        });

        Schema::create('publications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_project_id')->constrained('content_projects')->cascadeOnDelete();
            $table->string('platform', 32)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->jsonb('metadata');
            $table->string('media_checksum', 64);
            $table->unsignedInteger('metadata_version')->default(1);
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->string('timezone_snapshot')->default('Europe/London');
            $table->string('external_post_id')->nullable();
            $table->text('external_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('publication_id')->unique()->constrained('publications')->cascadeOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->string('review_hash', 64);
            $table->jsonb('review_payload');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('publication_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('publication_id')->constrained('publications')->cascadeOnDelete();
            $table->unsignedInteger('attempt');
            $table->string('stage', 64);
            $table->string('provider_request_id', 191)->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->jsonb('response_metadata');
            $table->boolean('retryable')->default(false);
            $table->text('error')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();
            $table->unique(['publication_id', 'attempt', 'stage']);
        });

        Schema::create('agent_decisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('decision_type', 64)->index();
            $table->uuid('subject_id')->nullable();
            $table->string('subject_type', 128)->nullable();
            $table->index(['subject_type', 'subject_id']);
            $table->jsonb('input_summary');
            $table->jsonb('output');
            $table->string('prompt_version')->nullable();
            $table->string('model')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('provider_usage', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 100)->index();
            $table->string('capability', 100);
            $table->uuid('subject_id')->nullable();
            $table->string('subject_type', 128)->nullable();
            $table->index(['subject_type', 'subject_id']);
            $table->string('request_hash', 64)->index();
            $table->decimal('estimated_cost_gbp', 10, 4)->default(0);
            $table->decimal('actual_cost_gbp', 10, 4)->nullable();
            $table->jsonb('usage')->nullable();
            $table->timestamps();
        });

        Schema::create('jobs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('queue', 64)->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table): void {
            $table->string('id', 191)->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid', 64)->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('cache', function (Blueprint $table): void {
            $table->string('key', 191)->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table): void {
            $table->string('key', 191)->primary();
            $table->string('owner', 191);
            $table->integer('expiration');
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id', 191)->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('provider_usage');
        Schema::dropIfExists('agent_decisions');
        Schema::dropIfExists('publication_attempts');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('publications');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('generation_requests');
        Schema::dropIfExists('scripts');
        Schema::dropIfExists('content_projects');
        Schema::dropIfExists('topic_candidates');
        Schema::dropIfExists('channels');
        Schema::dropIfExists('users');
    }
};
