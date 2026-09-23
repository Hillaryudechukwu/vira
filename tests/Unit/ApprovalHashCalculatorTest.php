<?php

namespace Tests\Unit;

use App\Modules\Approval\Application\ApprovalHashCalculator;
use PHPUnit\Framework\TestCase;

final class ApprovalHashCalculatorTest extends TestCase
{
    public function test_key_order_does_not_change_the_review_hash(): void
    {
        $calculator = new ApprovalHashCalculator;

        self::assertSame(
            $calculator->calculate(['metadata' => ['caption' => 'Hello', 'title' => 'Test'], 'platform' => 'youtube']),
            $calculator->calculate(['platform' => 'youtube', 'metadata' => ['title' => 'Test', 'caption' => 'Hello']]),
        );
    }

    public function test_content_change_invalidates_the_hash(): void
    {
        $calculator = new ApprovalHashCalculator;

        self::assertNotSame(
            $calculator->calculate(['caption' => 'Original']),
            $calculator->calculate(['caption' => 'Changed']),
        );
    }
}
