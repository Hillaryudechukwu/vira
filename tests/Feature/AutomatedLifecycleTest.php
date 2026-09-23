<?php

namespace Tests\Feature;

use App\Jobs\PublishApprovedPublication;
use App\Models\Channel;
use App\Models\Publication;
use App\Modules\Automation\Application\RunGuardedAutomation;
use App\Modules\Publishing\Enums\PublicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class AutomatedLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarded_run_builds_a_complete_four_platform_approval_packet_once(): void
    {
        Bus::fake();
        $channel = $this->channel();

        $run = $this->postJson("/api/v1/channels/{$channel->id}/automation/run")
            ->assertAccepted()
            ->assertJsonPath('status', 'awaiting_approval')
            ->assertJsonCount(4, 'content_project.publications')
            ->json();

        $this->assertDatabaseCount('topic_candidates', 10);
        $this->assertDatabaseCount('scripts', 1);
        $this->assertDatabaseCount('quality_checks', 1);
        $this->assertDatabaseCount('media_assets', 1);
        $this->assertDatabaseCount('budget_reservations', 1);

        $this->postJson("/api/v1/channels/{$channel->id}/automation/run")
            ->assertAccepted()
            ->assertJsonPath('id', $run['id']);
        $this->assertDatabaseCount('automation_runs', 1);

        $this->postJson("/api/v1/automation/{$run['id']}/approve", ['comment' => 'Reviewed all four private bundles.'])
            ->assertOk()
            ->assertJsonPath('status', 'scheduled');

        self::assertSame(4, Publication::query()->where('status', PublicationStatus::Queued)->count());
        Bus::assertDispatchedTimes(PublishApprovedPublication::class, 4);
    }

    public function test_metrics_create_a_traceable_performance_diagnosis(): void
    {
        $channel = $this->channel();
        $run = app(RunGuardedAutomation::class)->execute($channel);
        $publication = $run->contentProject->publications()->firstOrFail();

        $this->postJson("/api/v1/analytics/publications/{$publication->id}/snapshots", [
            'window' => '24h',
            'metrics' => [
                'impressions' => 1000, 'views' => 800, 'average_watch_percent' => 62,
                'completion_rate' => 45, 'rewatch_rate' => 12, 'shares' => 24,
                'saves' => 16, 'meaningful_comments' => 8, 'profile_visits' => 30,
                'followers_gained' => 10,
            ],
        ])->assertCreated()->assertJsonPath('qag_per_1000', '181.000');

        $this->assertDatabaseHas('metric_snapshots', ['publication_id' => $publication->id, 'window' => '24h']);
        $this->assertDatabaseHas('performance_reports', ['publication_id' => $publication->id]);
    }

    private function channel(): Channel
    {
        return Channel::query()->create([
            'name' => 'Automated Channel', 'slug' => 'automated-channel',
            'niche' => 'Behaviour-focused relationship education', 'default_timezone' => 'Europe/London',
            'operating_mode' => 'guarded', 'settings' => ['daily_generation_budget_gbp' => 20, 'monthly_budget_gbp' => 400],
        ]);
    }
}
