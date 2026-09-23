<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\ContentProject;
use App\Models\TopicCandidate;
use App\Modules\BillingOps\Application\ReserveBudgetAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class BudgetEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_expensive_operation_fails_closed_before_exceeding_the_daily_cap(): void
    {
        $channel = Channel::query()->create(['name' => 'Budget', 'slug' => 'budget', 'niche' => 'Test', 'settings' => ['daily_generation_budget_gbp' => 1, 'monthly_budget_gbp' => 5]]);
        $topic = TopicCandidate::query()->create(['channel_id' => $channel->id, 'title' => 'Topic', 'angle' => 'Angle', 'content_pillar' => 'test', 'status' => 'selected', 'component_scores' => [], 'viral_score' => 80]);
        $project = ContentProject::query()->create(['channel_id' => $channel->id, 'topic_candidate_id' => $topic->id, 'working_title' => 'Project', 'objective' => 'Test', 'status' => 'producing']);
        $action = app(ReserveBudgetAction::class);
        $action->execute($channel, $project, 'fake', 'video', .75);

        $this->expectException(ValidationException::class);
        $action->execute($channel, $project, 'fake', 'regeneration', .50);
    }
}
