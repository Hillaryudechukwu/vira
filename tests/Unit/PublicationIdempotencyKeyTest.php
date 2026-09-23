<?php

namespace Tests\Unit;

use App\Modules\Publishing\Application\PublicationIdempotencyKey;
use App\Modules\Shared\Enums\Platform;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PublicationIdempotencyKeyTest extends TestCase
{
    public function test_identical_inputs_produce_the_same_key(): void
    {
        $service = new PublicationIdempotencyKey;
        $date = new DateTimeImmutable('2026-10-01T18:00:00+00:00');

        $first = $service->make(Platform::YouTube, 'channel-1', 'project-1', str_repeat('a', 64), 1, $date);
        $second = $service->make(Platform::YouTube, 'channel-1', 'project-1', str_repeat('a', 64), 1, $date);

        self::assertSame($first, $second);
        self::assertSame(64, strlen($first));
    }
}
