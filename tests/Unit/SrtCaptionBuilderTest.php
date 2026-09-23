<?php

namespace Tests\Unit;

use App\Modules\Production\Application\SrtCaptionBuilder;
use PHPUnit\Framework\TestCase;

final class SrtCaptionBuilderTest extends TestCase
{
    public function test_it_builds_a_valid_srt_timeline(): void
    {
        $srt = (new SrtCaptionBuilder)->build([
            ['start_ms' => 0, 'end_ms' => 2500, 'caption' => 'FIRST LINE'],
            ['start_ms' => 2500, 'end_ms' => 61000, 'caption' => 'SECOND LINE'],
        ]);

        self::assertStringContainsString('00:00:00,000 --> 00:00:02,500', $srt);
        self::assertStringContainsString('00:00:02,500 --> 00:01:01,000', $srt);
    }
}
