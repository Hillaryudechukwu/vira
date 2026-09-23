<?php

namespace App\Modules\Publishing\DTO;

final readonly class PublishResult
{
    public function __construct(
        public string $externalPostId,
        public ?string $externalUrl,
        public string $status,
        public array $metadata = [],
    ) {}
}
