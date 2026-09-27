<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignUuid('content_project_id')->nullable()->constrained('content_projects')->nullOnDelete();
            $table->date('run_on');
            $table->string('status', 32)->default('running')->index();
            $table->string('current_stage', 64)->default('discover');
            $table->jsonb('context');
            $table->text('failure')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['channel_id', 'run_on']);
        });

        Schema::create('budget_reservations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignUuid('content_project_id')->nullable()->constrained('content_projects')->nullOnDelete();
            $table->string('provider', 100);
            $table->string('capability', 100);
            $table->decimal('amount_gbp', 10, 4);
            $table->string('status', 32)->default('reserved')->index();
            $table->timestamp('reserved_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quality_checks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_project_id')->constrained('content_projects')->cascadeOnDelete();
            $table->string('check_type', 64);
            $table->string('status', 32)->index();
            $table->jsonb('findings');
            $table->timestamp('checked_at');
            $table->timestamps();
        });

        Schema::create('metric_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('publication_id')->constrained('publications')->cascadeOnDelete();
            $table->string('window', 16);
            $table->timestamp('observed_at');
            $table->jsonb('raw_metrics');
            $table->jsonb('normalised_metrics');
            $table->jsonb('availability');
            $table->string('source', 64)->default('manual');
            $table->string('definition_version', 32)->default('v1');
            $table->timestamps();
            $table->unique(['publication_id', 'window']);
        });

        Schema::create('performance_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('publication_id')->unique()->constrained('publications')->cascadeOnDelete();
            $table->decimal('performance_score', 6, 2);
            $table->decimal('qag_per_1000', 10, 3)->nullable();
            $table->jsonb('diagnosis');
            $table->text('recommended_action');
            $table->decimal('confidence', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reports');
        Schema::dropIfExists('metric_snapshots');
        Schema::dropIfExists('quality_checks');
        Schema::dropIfExists('budget_reservations');
        Schema::dropIfExists('automation_runs');
    }
};
