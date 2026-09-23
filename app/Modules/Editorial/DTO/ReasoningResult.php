<?php

namespace App\Modules\Editorial\DTO;

final readonly class ReasoningResult
{
    public function __construct(
        public array $data,
        public string $provider,
        public string $model,
        public string $promptVersion,
        public float $estimatedCostGbp = 0,
    ) {}
}
