<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TopicCandidate;
use App\Modules\Research\Application\SelectTopicCandidateAction;
use App\Modules\Research\Application\TopicScoreCalculator;
use App\Modules\Research\Enums\TopicStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TopicCandidateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $topics = TopicCandidate::query()
            ->when($request->string('channel_id')->isNotEmpty(), fn ($query) => $query->where('channel_id', $request->string('channel_id')->toString()))
            ->orderByDesc('viral_score')
            ->paginate(25);

        return response()->json($topics);
    }

    public function store(Request $request, TopicScoreCalculator $calculator): JsonResponse
    {
        $componentRules = collect(array_keys(config('vira.topic_score_weights')))
            ->mapWithKeys(fn (string $name): array => ["scores.{$name}" => ['required', 'numeric', 'between:0,100']])
            ->all();

        $data = $request->validate(array_merge([
            'channel_id' => ['required', 'uuid', 'exists:channels,id'],
            'title' => ['required', 'string', 'max:255'],
            'angle' => ['required', 'string', 'max:4000'],
            'content_pillar' => ['required', 'string', 'max:100'],
            'scores' => ['required', 'array'],
            'risk_penalty' => ['sometimes', 'numeric', 'between:0,100'],
            'confidence' => ['sometimes', 'numeric', 'between:0,100'],
        ], $componentRules));

        $score = $calculator->calculate($data['scores'], (float) ($data['risk_penalty'] ?? 0));

        $topic = TopicCandidate::query()->create([
            'channel_id' => $data['channel_id'],
            'title' => $data['title'],
            'angle' => $data['angle'],
            'content_pillar' => $data['content_pillar'],
            'status' => $score->qualified ? TopicStatus::Qualified : TopicStatus::Discovered,
            'component_scores' => $score->components,
            'viral_score' => $score->score,
            'risk_score' => $score->riskPenalty,
            'confidence' => $data['confidence'] ?? 50,
            'score_explanation' => [
                'formula' => 'weighted components minus risk penalty',
                'threshold' => config('vira.topic_score_threshold'),
            ],
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json($topic, 201);
    }

    public function select(TopicCandidate $topicCandidate, SelectTopicCandidateAction $action): JsonResponse
    {
        return response()->json($action->execute($topicCandidate), 201);
    }
}
