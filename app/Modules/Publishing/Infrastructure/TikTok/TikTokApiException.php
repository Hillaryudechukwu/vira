<?php

namespace App\Modules\Publishing\Infrastructure\TikTok;

use RuntimeException;

final class TikTokApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        public readonly ?string $logId = null,
        public readonly bool $retryable = false,
    ) {
        parent::__construct($message);
    }
}
