<?php

namespace App\Modules\Quality\Application;

use App\Models\ContentProject;
use App\Models\QualityCheck;
use Illuminate\Validation\ValidationException;

final class RunProjectQualityGate
{
    public function execute(ContentProject $project): array
    {
        $script = $project->scripts()->latest('version')->firstOrFail();
        $findings = [];

        foreach ($script->claims as $index => $claim) {
            $status = $claim['evidence_status'] ?? 'required';
            $risk = $claim['risk'] ?? 'high';
            if ($status === 'prohibited' || ($risk === 'high' && ! in_array($status, ['sourced', 'common_knowledge'], true))) {
                $findings[] = ['claim' => $index, 'severity' => 'blocking', 'message' => 'High-risk or prohibited claim lacks approved evidence.'];
            }
        }

        $hostilePatterns = ['all women', 'all men', 'women always', 'men always', 'retaliate', 'spy on'];
        foreach ($hostilePatterns as $pattern) {
            if (str_contains(mb_strtolower($script->narration), $pattern)) {
                $findings[] = ['severity' => 'blocking', 'message' => "Unsafe or generalising language: {$pattern}"];
            }
        }

        $check = QualityCheck::query()->create([
            'content_project_id' => $project->id,
            'check_type' => 'editorial_policy',
            'status' => $findings === [] ? 'passed' : 'failed',
            'findings' => $findings,
            'checked_at' => now(),
        ]);

        if ($findings !== []) {
            throw ValidationException::withMessages(['quality' => 'The project failed the editorial policy gate.']);
        }

        return ['passed' => true, 'check_id' => $check->id, 'findings' => []];
    }
}
