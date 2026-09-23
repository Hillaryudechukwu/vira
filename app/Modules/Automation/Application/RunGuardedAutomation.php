<?php

namespace App\Modules\Automation\Application;

use App\Models\AutomationRun;
use App\Models\Channel;
use App\Models\MediaAsset;
use App\Models\TopicCandidate;
use App\Modules\BillingOps\Application\ReserveBudgetAction;
use App\Modules\Editorial\Application\GenerateScriptAction;
use App\Modules\Production\Application\SrtCaptionBuilder;
use App\Modules\Publishing\Application\PreparePublicationAction;
use App\Modules\Quality\Application\RunProjectQualityGate;
use App\Modules\Research\Application\SelectTopicCandidateAction;
use App\Modules\Research\Application\TopicScoreCalculator;
use App\Modules\Research\Enums\TopicStatus;
use App\Modules\Shared\Enums\Platform;
use Carbon\CarbonImmutable;
use Throwable;

final readonly class RunGuardedAutomation
{
    public function __construct(
        private TopicScoreCalculator $scores,
        private SelectTopicCandidateAction $select,
        private GenerateScriptAction $scripts,
        private ReserveBudgetAction $budgets,
        private RunProjectQualityGate $quality,
        private PreparePublicationAction $publications,
        private SrtCaptionBuilder $captions,
    ) {}

    public function execute(Channel $channel): AutomationRun
    {
        $existing = AutomationRun::query()->where('channel_id', $channel->id)->whereDate('run_on', today())->first();
        if ($existing !== null) {
            return $existing->load('contentProject.publications.approvalRequest');
        }

        $run = AutomationRun::query()->create(['channel_id' => $channel->id, 'run_on' => today(), 'status' => 'running', 'current_stage' => 'discover', 'started_at' => now(), 'context' => []]);

        try {
            $topics = $this->discover($channel);
            $run->update(['current_stage' => 'select', 'context' => ['candidate_ids' => $topics->pluck('id')->all()]]);
            $project = $this->select->execute($topics->sortByDesc('viral_score')->first());
            $run->update(['content_project_id' => $project->id, 'current_stage' => 'script']);
            $script = $this->scripts->execute($project);
            $this->budgets->execute($channel, $project, 'manual', 'video_master', (float) config('vira.estimated_master_cost_gbp', 0));
            $run->update(['current_stage' => 'quality']);
            $quality = $this->quality->execute($project);

            $manifest = ['project_id' => $project->id, 'script_id' => $script->id, 'script_version' => $script->version, 'captions' => $this->captions->build($script->structure['beats'])];
            $checksum = hash('sha256', json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            MediaAsset::query()->create(['content_project_id' => $project->id, 'asset_type' => 'master', 'disk' => 'local', 'object_key' => "manual/{$project->id}/master.mp4", 'mime_type' => 'video/mp4', 'byte_size' => 0, 'checksum' => $checksum, 'width' => 1080, 'height' => 1920, 'duration_ms' => 45000, 'frame_rate' => 30, 'provenance' => ['provider' => 'manual', 'manifest' => $manifest], 'rights_status' => 'pending_review']);

            $run->update(['current_stage' => 'prepare_publications']);
            $base = CarbonImmutable::now()->addHour()->startOfMinute();
            foreach (Platform::cases() as $offset => $platform) {
                $this->publications->execute($project->fresh('channel'), $platform, $this->metadata($platform, $project->working_title), $checksum, $base->addMinutes($offset * 30), "{$platform->value}-unconnected");
            }

            $run->update(['status' => 'awaiting_approval', 'current_stage' => 'approval', 'completed_at' => now(), 'context' => ['candidate_ids' => $topics->pluck('id')->all(), 'quality' => $quality, 'media_checksum' => $checksum]]);
        } catch (Throwable $exception) {
            $run->update(['status' => 'failed', 'failure' => mb_substr($exception->getMessage(), 0, 2000), 'completed_at' => now()]);
            throw $exception;
        }

        return $run->fresh()->load('contentProject.scripts', 'contentProject.publications.approvalRequest');
    }

    private function discover(Channel $channel)
    {
        $ideas = [
            ['When silence becomes punishment', 'Distinguish healthy space from conditional withdrawal.', 'hidden_manipulation_patterns'],
            ['When every boundary becomes a loyalty test', 'Show how a respectful boundary differs from rejection.', 'realistic_scenarios'],
            ['Peace is not permanent agreement', 'Contrast conflict repair with coerced agreement.', 'healthy_unhealthy'],
            ['When affection becomes a reward', 'Explain conditional affection with cautious behaviour-focused language.', 'hidden_manipulation_patterns'],
            ['Space versus silent treatment', 'Give viewers observable distinctions without diagnosing anyone.', 'healthy_unhealthy'],
            ['What a healthy disagreement sounds like', 'Model a calm repair-oriented exchange.', 'positive_repair'],
            ['The hidden cost of always giving in', 'Explore self-silencing as an observation, not a diagnosis.', 'things_men_fear_to_admit'],
            ['A boundary can be loving', 'Reframe boundaries as clarity rather than punishment.', 'positive_repair'],
            ['Why the tension disappears only when you surrender', 'Name the pattern without alleging intent.', 'realistic_scenarios'],
            ['One question before you apologise again', 'Offer a reflective, non-hostile viewer prompt.', 'comment_led'],
        ];

        return collect($ideas)->map(function (array $idea, int $index) use ($channel): TopicCandidate {
            $components = ['trend_velocity' => 82 - $index, 'emotional_resonance' => 92 - ($index % 4), 'relatability' => 90, 'curiosity_gap' => 88 - ($index % 3), 'share_intent' => 84, 'comment_potential' => 80, 'channel_fit' => 96, 'novelty' => 78 + ($index % 5)];
            $score = $this->scores->calculate($components, 2);

            return TopicCandidate::query()->create(['channel_id' => $channel->id, 'title' => $idea[0], 'angle' => $idea[1], 'content_pillar' => $idea[2], 'status' => $score->qualified ? TopicStatus::Qualified : TopicStatus::Discovered, 'component_scores' => $components, 'viral_score' => $score->score, 'risk_score' => 2, 'confidence' => 70, 'score_explanation' => ['source' => 'configured cold-start portfolio', 'uncertainty' => 'No first-party performance history yet.'], 'expires_at' => now()->addDays(7)]);
        });
    }

    private function metadata(Platform $platform, string $title): array
    {
        $caption = "{$title}. A calm, behaviour-focused distinction—not a diagnosis. What difference have you noticed?";

        return ['title' => $platform === Platform::YouTube ? $title : null, 'caption' => $caption, 'hashtags' => ['relationshipskills', 'healthybounds', 'menswellbeing'], 'synthetic_media' => true, 'privacy' => 'private'];
    }
}
