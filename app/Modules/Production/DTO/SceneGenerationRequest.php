<?php

namespace App\Modules\Production\DTO;

final readonly class SceneGenerationRequest
{
    public function __construct(
        public string $projectId,
        public string $sceneId,
        public string $prompt,
        public string $negativePrompt,
        public int $durationSeconds,
        public string $aspectRatio = '9:16',
        public array $characterReferences = [],
    ) {}
}
