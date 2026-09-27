<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\ContentProject;
use App\Models\SocialAccount;
use App\Models\TiktokPost;
use App\Models\TopicCandidate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class TikTokIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('vira.operator_token', 'review-token');
        config()->set('tiktok.client_key', 'client-key');
        config()->set('tiktok.client_secret', 'client-secret');
        config()->set('tiktok.redirect_uri', 'https://vira.example/integrations/tiktok/callback');
        config()->set('tiktok.allowed_media_hosts', ['vira.example']);
    }

    public function test_operator_can_connect_tiktok_and_tokens_are_encrypted(): void
    {
        $this->withSession(['vira_operator_authenticated' => true])
            ->get('/integrations/tiktok/connect')
            ->assertRedirectContains('https://www.tiktok.com/v2/auth/authorize/');
        $state = session('tiktok_oauth_state');

        Http::fake([
            'open.tiktokapis.com/v2/oauth/token/' => Http::response(['access_token' => 'secret-access', 'refresh_token' => 'secret-refresh', 'open_id' => 'creator-1', 'scope' => 'user.info.basic,video.upload,video.publish', 'expires_in' => 86400, 'refresh_expires_in' => 31536000]),
            'open.tiktokapis.com/v2/user/info/*' => Http::response(['data' => ['user' => ['open_id' => 'creator-1', 'display_name' => 'VIRA Reviewer', 'avatar_url' => 'https://example.com/avatar.jpg']], 'error' => ['code' => 'ok']]),
        ]);

        $this->withSession(['vira_operator_authenticated' => true, 'tiktok_oauth_state' => $state])
            ->get('/integrations/tiktok/callback?code=oauth-code&state='.$state)
            ->assertRedirect(route('dashboard'));

        $account = SocialAccount::query()->firstOrFail();
        self::assertSame('VIRA Reviewer', $account->display_name);
        self::assertSame('secret-access', $account->access_token);
        $this->assertDatabaseMissing('social_accounts', ['access_token' => 'secret-access']);
    }

    public function test_draft_upload_and_direct_post_use_separate_tiktok_endpoints(): void
    {
        $account = $this->account();
        Http::fake([
            'open.tiktokapis.com/v2/post/publish/creator_info/query/' => Http::response(['data' => ['creator_nickname' => 'Reviewer', 'privacy_level_options' => ['SELF_ONLY'], 'comment_disabled' => false, 'duet_disabled' => false, 'stitch_disabled' => false, 'max_video_post_duration_sec' => 300], 'error' => ['code' => 'ok']]),
            'open.tiktokapis.com/v2/post/publish/inbox/video/init/' => Http::response(['data' => ['publish_id' => 'draft-publish-id'], 'error' => ['code' => 'ok']]),
            'open.tiktokapis.com/v2/post/publish/video/init/' => Http::response(['data' => ['publish_id' => 'direct-publish-id'], 'error' => ['code' => 'ok']]),
        ]);
        $session = ['vira_operator_authenticated' => true];
        $common = ['social_account_id' => $account->id, 'video_url' => 'https://vira.example/media/demo.mp4', 'caption' => 'A reviewed caption #VIRA', 'is_aigc' => '1', 'rights_confirmed' => '1'];

        $this->withSession($session)->post('/tiktok/drafts', $common)->assertRedirect();
        $this->withSession($session)->post('/tiktok/publish', [...$common, 'privacy_level' => 'SELF_ONLY', 'consent' => '1'])->assertRedirect();

        self::assertSame('draft-publish-id', TiktokPost::query()->where('mode', 'draft')->value('publish_id'));
        self::assertSame('direct-publish-id', TiktokPost::query()->where('mode', 'direct')->value('publish_id'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/inbox/video/init/') && $request['source_info']['source'] === 'PULL_FROM_URL');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/publish/video/init/') && $request['post_info']['privacy_level'] === 'SELF_ONLY' && $request['post_info']['is_aigc'] === true);
    }

    public function test_dashboard_requires_operator_login_and_accepts_a_review_video(): void
    {
        $this->get('/dashboard')->assertRedirect(route('operator.login'));
        $this->post('/operator/login', ['token' => 'review-token'])->assertRedirect(route('dashboard'));
        $project = $this->project();

        $this->withSession(['vira_operator_authenticated' => true])->post('/media', [
            'content_project_id' => $project->id,
            'video' => UploadedFile::fake()->create('review.mp4', 1024, 'video/mp4'),
            'rights_confirmed' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('media_assets', ['content_project_id' => $project->id, 'disk' => 'public', 'rights_status' => 'operator_confirmed']);
    }

    public function test_operator_can_create_the_first_content_package_from_the_dashboard(): void
    {
        $channel = Channel::query()->create([
            'name' => 'VIRA',
            'slug' => 'vira',
            'niche' => 'Education',
            'settings' => [],
        ]);

        $this->withSession(['vira_operator_authenticated' => true])
            ->post('/automation/run', ['channel_id' => $channel->id])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('automation_runs', [
            'channel_id' => $channel->id,
            'status' => 'awaiting_approval',
        ]);
        $this->assertDatabaseCount('content_projects', 1);
    }

    private function account(): SocialAccount
    {
        return SocialAccount::query()->create([
            'platform' => 'tiktok', 'external_account_id' => 'creator-1', 'display_name' => 'Reviewer', 'status' => 'connected',
            'access_token' => 'access', 'refresh_token' => 'refresh', 'scopes' => ['user.info.basic', 'video.upload', 'video.publish'],
            'expires_at' => now()->addDay(), 'last_verified_at' => now(),
        ]);
    }

    private function project(): ContentProject
    {
        $channel = Channel::query()->create(['name' => 'TikTok', 'slug' => 'tiktok', 'niche' => 'Education', 'settings' => []]);
        $topic = TopicCandidate::query()->create(['channel_id' => $channel->id, 'title' => 'Review', 'angle' => 'Review flow', 'content_pillar' => 'test', 'status' => 'selected', 'component_scores' => [], 'viral_score' => 80]);

        return ContentProject::query()->create(['channel_id' => $channel->id, 'topic_candidate_id' => $topic->id, 'working_title' => 'TikTok review video', 'objective' => 'Demonstrate publishing', 'status' => 'producing']);
    }
}
