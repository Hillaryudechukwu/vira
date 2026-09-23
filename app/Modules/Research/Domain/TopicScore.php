<?php

namespace App\Modules\Research\Domain;

final readonly class TopicScore
{
    public function __construct(
        public float $score,
        public float $riskPenalty,
        public bool $qualified,
        public array $components,
    ) {}
}
