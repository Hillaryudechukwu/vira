<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('channel_id')->nullable()->constrained('channels')->nullOnDelete();
            $table->string('platform', 32)->index();
            $table->string('external_account_id', 191);
            $table->string('display_name', 191)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->string('status', 32)->default('connected')->index();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->json('scopes');
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('refresh_expires_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
            $table->unique(['platform', 'external_account_id']);
        });

        Schema::create('tiktok_posts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->foreignUuid('publication_id')->nullable()->constrained('publications')->nullOnDelete();
            $table->string('mode', 16);
            $table->string('status', 32)->default('draft')->index();
            $table->text('video_url');
            $table->string('video_checksum', 64)->nullable();
            $table->text('caption')->nullable();
            $table->string('privacy_level', 40)->nullable();
            $table->boolean('disable_comment')->default(false);
            $table->boolean('disable_duet')->default(false);
            $table->boolean('disable_stitch')->default(false);
            $table->boolean('is_aigc')->default(true);
            $table->boolean('consent_confirmed')->default(false);
            $table->string('publish_id', 191)->nullable()->unique();
            $table->json('request_payload');
            $table->json('response_payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_posts');
        Schema::dropIfExists('social_accounts');
    }
};
