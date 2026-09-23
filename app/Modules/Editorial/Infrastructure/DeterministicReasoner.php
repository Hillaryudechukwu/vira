<?php

namespace App\Modules\Editorial\Infrastructure;

use App\Modules\Editorial\Contracts\StructuredReasoner;
use App\Modules\Editorial\DTO\ReasoningResult;

final class DeterministicReasoner implements StructuredReasoner
{
    public function generateScript(array $context): ReasoningResult
    {
        $title = (string) ($context['title'] ?? 'When a boundary becomes a test');

        $beats = [
            ['start_ms' => 0, 'end_ms' => 3000, 'purpose' => 'hook', 'narration' => 'A boundary should not cost you love.', 'caption' => 'A BOUNDARY SHOULD NOT COST LOVE'],
            ['start_ms' => 3000, 'end_ms' => 12000, 'purpose' => 'scenario', 'narration' => 'But sometimes every no is answered with distance, silence, or a sudden loss of affection.', 'caption' => 'WHEN EVERY “NO” HAS A PRICE'],
            ['start_ms' => 12000, 'end_ms' => 23000, 'purpose' => 'escalation', 'narration' => 'Soon, you stop asking what is right and start asking what will make the tension disappear.', 'caption' => 'YOU LEARN TO RESTORE PEACE'],
            ['start_ms' => 23000, 'end_ms' => 33000, 'purpose' => 'distinction', 'narration' => 'Disagreement is normal. Making connection conditional on surrender is something different.', 'caption' => 'DISAGREEMENT IS NOT PUNISHMENT'],
            ['start_ms' => 33000, 'end_ms' => 45000, 'purpose' => 'payoff', 'narration' => 'If saying no always costs you closeness, your yes may no longer be freely chosen. Learn the difference.', 'caption' => 'LEARN THE DIFFERENCE'],
        ];

        $narration = implode(' ', array_column($beats, 'narration'));

        return new ReasoningResult(
            data: [
                'title' => $title,
                'hook' => $beats[0]['narration'],
                'target_duration_seconds' => 45,
                'beats' => $beats,
                'narration' => $narration,
                'claims' => [[
                    'text' => 'Making connection conditional on surrender can be an unhealthy relationship pattern.',
                    'risk' => 'medium',
                    'evidence_status' => 'observation',
                ]],
                'cta' => 'Have you experienced the difference?',
            ],
            provider: 'local',
            model: 'deterministic-fixture',
            promptVersion: 'script-v1',
        );
    }
}
