<?php

namespace App\Modules\Research\Application;

use App\Modules\Research\Domain\TopicScore;
use InvalidArgumentException;

final class TopicScoreCalculator
{
    public function calculate(array $components, float $riskPenalty = 0): TopicScore
    {
        $weights = config('vira.topic_score_weights');
        $missing = array_diff(array_keys($weights), array_keys($components));

        if ($missing !== []) {
            throw new InvalidArgumentException('Missing topic score components: '.implode(', ', $missing));
        }

        foreach ($weights as $name => $weight) {
            $value = (float) $components[$name];
            if ($value < 0 || $value > 100) {
                throw new InvalidArgumentException("{$name} must be between 0 and 100.");
            }
        }

        if ($riskPenalty < 0 || $riskPenalty > 100) {
            throw new InvalidArgumentException('Risk penalty must be between 0 and 100.');
        }

        $weighted = array_reduce(
            array_keys($weights),
            fn (float $total, string $name): float => $total + ((float) $components[$name] * $weights[$name]),
            0.0,
        );

        $score = round(max(0, min(100, $weighted - $riskPenalty)), 2);

        return new TopicScore(
            score: $score,
            riskPenalty: $riskPenalty,
            qualified: $score >= (float) config('vira.topic_score_threshold', 70),
            components: $components,
        );
    }
}
