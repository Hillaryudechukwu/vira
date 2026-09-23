<?php

namespace App\Modules\Production\DTO;

use App\Modules\Production\Enums\ProviderJobStatus;

final readonly class ProviderJob
{
    public function __construct(
        public string $id,
        public string $provider,
        public ProviderJobStatus $status,
        public array $metadata = [],
    ) {}
}
