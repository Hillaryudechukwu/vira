<?php

namespace App\Modules\Editorial\Application;

use App\Models\AgentDecision;
use App\Models\ContentProject;
use App\Models\Script;
use App\Modules\Editorial\Contracts\StructuredReasoner;
use App\Modules\Editorial\Enums\ContentProjectStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class GenerateScriptAction
{
    public function __construct(private StructuredReasoner $reasoner) {}

    public function execute(ContentProject $project): Script
    {
        $project->loadMissing('topicCandidate');
        $result = $this->reasoner->generateScript([
            'title' => $project->working_title,
            'objective' => $project->objective,
            'topic' => $project->topicCandidate?->only(['title', 'angle', 'content_pillar']),
            'target_duration_ms' => $project->target_duration_ms,
        ]);

        $this->validateResult($result->data);

        return DB::transaction(function () use ($project, $result): Script {
            $version = ((int) $project->current_script_version) + 1;
            $narration = $result->data['narration'];

            $script = Script::query()->create([
                'content_project_id' => $project->id,
                'version' => $version,
                'narration' => $narration,
                'word_count' => Str::wordCount($narration),
                'estimated_duration_ms' => $result->data['target_duration_seconds'] * 1000,
                'structure' => ['beats' => $result->data['beats'], 'cta' => $result->data['cta']],
                'claims' => $result->data['claims'],
                'model_metadata' => ['provider' => $result->provider, 'model' => $result->model],
                'prompt_version' => $result->promptVersion,
                'status' => 'draft',
            ]);

            $project->update([
                'current_script_version' => $version,
                'status' => ContentProjectStatus::Producing,
            ]);

            AgentDecision::query()->create([
                'decision_type' => 'script_generation',
                'subject_type' => ContentProject::class,
                'subject_id' => $project->id,
                'input_summary' => ['title' => $project->working_title],
                'output' => ['script_id' => $script->id, 'version' => $version],
                'prompt_version' => $result->promptVersion,
                'model' => $result->model,
                'confidence' => 90,
            ]);

            return $script;
        });
    }

    private function validateResult(array $data): void
    {
        foreach (['title', 'hook', 'target_duration_seconds', 'beats', 'narration', 'claims', 'cta'] as $key) {
            if (! array_key_exists($key, $data)) {
                throw new \UnexpectedValueException("Structured reasoner omitted required key: {$key}");
            }
        }

        if (! is_array($data['beats']) || count($data['beats']) < 3) {
            throw new \UnexpectedValueException('Script must contain at least three beats.');
        }
    }
}
