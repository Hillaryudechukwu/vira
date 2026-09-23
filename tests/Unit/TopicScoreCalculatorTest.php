<?php

namespace Tests\Unit;

use App\Modules\Research\Application\TopicScoreCalculator;
use InvalidArgumentException;
use Tests\TestCase;

final class TopicScoreCalculatorTest extends TestCase
{
    public function test_it_calculates_a_weighted_score_and_qualification(): void
    {
        $score = app(TopicScoreCalculator::class)->calculate([
            'trend_velocity' => 80,
            'emotional_resonance' => 90,
            'relatability' => 90,
            'curiosity_gap' => 85,
            'share_intent' => 85,
            'comment_potential' => 75,
            'channel_fit' => 98,
            'novelty' => 70,
        ], riskPenalty: 5);

        self::assertSame(80.56, $score->score);
        self::assertTrue($score->qualified);
    }

    public function test_it_rejects_out_of_range_components(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(TopicScoreCalculator::class)->calculate([
            'trend_velocity' => 101,
            'emotional_resonance' => 90,
            'relatability' => 90,
            'curiosity_gap' => 85,
            'share_intent' => 85,
            'comment_potential' => 75,
            'channel_fit' => 98,
            'novelty' => 70,
        ]);
    }
}
