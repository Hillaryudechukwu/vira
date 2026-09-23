<?php

namespace App\Modules\Production\DTO;

final readonly class GeneratedMedia
{
    public function __construct(
        public string $path,
        public string $mimeType,
        public string $checksum,
        public int $durationMs,
        public array $provenance,
    ) {}
}
