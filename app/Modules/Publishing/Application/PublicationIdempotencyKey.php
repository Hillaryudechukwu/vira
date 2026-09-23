<?php

namespace App\Modules\Publishing\Application;

use App\Modules\Shared\Enums\Platform;
use DateTimeInterface;

final class PublicationIdempotencyKey
{
    public function make(
        Platform $platform,
        string $accountId,
        string $projectId,
        string $mediaChecksum,
        int $metadataVersion,
        DateTimeInterface $scheduledFor,
    ): string {
        return hash('sha256', implode('|', [
            $platform->value,
            $accountId,
            $projectId,
            $mediaChecksum,
            (string) $metadataVersion,
            $scheduledFor->format(DATE_ATOM),
        ]));
    }
}
