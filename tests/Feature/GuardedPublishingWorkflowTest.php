<?php

namespace Tests\Feature;

use App\Jobs\PublishApprovedPublication;
use App\Models\Channel;
use App\Modules\Publishing\Enums\PublicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class GuardedPublishingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_topic_can_become_an_approved_queued_publication(): void
    {
        Bus::fake();

        $channel = Channel::query()->create([
            'name' => 'Hidden Relationship Patterns',
            'slug' => 'hidden-patterns',
            'niche' => 'Relationship education',
            'default_timezone' => 'Europe/London',
            'operating_mode' => 'guarded',
            'settings' => [],
        ]);

        $topic = $this->postJson('/api/v1/topics', [
            'channel_id' => $channel->id,
            'title' => 'When silence becomes punishment',
            'angle' => 'Distinguish healthy space from conditional withdrawal.',
            'content_pillar' => 'hidden_manipulation_patterns',
            'risk_penalty' => 2,
            'confidence' => 85,
            'scores' => [
                'trend_velocity' => 90,
                'emotional_resonance' => 92,
                'relatability' => 95,
                'curiosity_gap' => 85,
                'share_intent' => 88,
                'comment_potential' => 80,
                'channel_fit' => 98,
                'novelty' => 75,
            ],
        ])->assertCreated()->json();

        $project = $this->postJson("/api/v1/topics/{$topic['id']}/select")
            ->assertCreated()
            ->json();

        $this->postJson("/api/v1/projects/{$project['id']}/generate-script")
            ->assertCreated()
            ->assertJsonPath('version', 1);

        $publication = $this->postJson("/api/v1/projects/{$project['id']}/prepare-publication", [
            'platform' => 'youtube',
            'metadata' => [
                'title' => 'When Silence Becomes Punishment',
                'caption' => 'Healthy space communicates. Punishment demands surrender.',
                'hashtags' => ['relationships', 'mensmentalhealth'],
            ],
            'media_checksum' => str_repeat('a', 64),
            'scheduled_for' => now()->addHour()->toIso8601String(),
            'account_reference' => 'youtube-channel-1',
        ])->assertCreated()->json();

        $approvalId = $publication['approval_request']['id'];
        $this->postJson("/api/v1/approvals/{$approvalId}/approve", ['comment' => 'Reviewed.'])
            ->assertOk()
            ->assertJsonPath('publication.status', PublicationStatus::Approved->value);

        $this->postJson("/api/v1/publications/{$publication['id']}/publish")
            ->assertStatus(202);

        Bus::assertDispatched(PublishApprovedPublication::class, fn ($job) => $job->publicationId === $publication['id']);
    }

    public function test_unapproved_publication_cannot_be_dispatched(): void
    {
        $this->postJson('/api/v1/publications/00000000-0000-0000-0000-000000000000/publish')
            ->assertNotFound();
    }
}
