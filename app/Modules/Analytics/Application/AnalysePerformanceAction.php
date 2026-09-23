<?php

namespace App\Modules\Analytics\Application;

use App\Models\MetricSnapshot;
use App\Models\PerformanceReport;
use App\Models\Publication;

final class AnalysePerformanceAction
{
    public function execute(Publication $publication, array $metrics, string $window = 'manual'): PerformanceReport
    {
        $impressions = max(0, (int) ($metrics['impressions'] ?? 0));
        $views = max(0, (int) ($metrics['views'] ?? 0));
        $normalised = [
            'view_rate' => $impressions > 0 ? $views / $impressions : null,
            'average_watch_percent' => $this->rate($metrics, 'average_watch_percent'),
            'completion_rate' => $this->rate($metrics, 'completion_rate'),
            'rewatch_rate' => $this->rate($metrics, 'rewatch_rate'),
            'share_rate' => $views > 0 ? (int) ($metrics['shares'] ?? 0) / $views : null,
            'save_rate' => $views > 0 ? (int) ($metrics['saves'] ?? 0) / $views : null,
            'follow_rate' => $views > 0 ? (int) ($metrics['followers_gained'] ?? 0) / $views : null,
        ];

        MetricSnapshot::query()->updateOrCreate(
            ['publication_id' => $publication->id, 'window' => $window],
            ['observed_at' => now(), 'raw_metrics' => $metrics, 'normalised_metrics' => $normalised, 'availability' => array_fill_keys(array_keys($metrics), true), 'source' => 'api'],
        );

        $qag = $impressions > 0 ? (((int) ($metrics['followers_gained'] ?? 0) * 5 + (int) ($metrics['shares'] ?? 0) * 3 + (int) ($metrics['saves'] ?? 0) * 2 + (int) ($metrics['meaningful_comments'] ?? 0) * 1.5 + (int) ($metrics['profile_visits'] ?? 0) * .5) / $impressions * 1000) : null;
        $availableRates = array_values(array_filter([$normalised['average_watch_percent'], $normalised['completion_rate'], $normalised['rewatch_rate'], $normalised['share_rate'], $normalised['save_rate'], $normalised['follow_rate']], fn ($value) => $value !== null));
        $score = $availableRates === [] ? 0 : min(100, array_sum($availableRates) / count($availableRates) * 100);
        $weakest = collect($normalised)->filter(fn ($value) => $value !== null)->sort()->keys()->first() ?? 'insufficient_data';

        return PerformanceReport::query()->updateOrCreate(
            ['publication_id' => $publication->id],
            [
                'performance_score' => round($score, 2),
                'qag_per_1000' => $qag === null ? null : round($qag, 3),
                'diagnosis' => ['observed' => $normalised, 'likely_explanation' => "The weakest measured stage is {$weakest}.", 'alternative_explanations' => ['platform distribution', 'topic demand', 'publish timing'], 'window' => $window],
                'recommended_action' => "Run a controlled next test changing only the creative variable most related to {$weakest}.",
                'confidence' => count($availableRates) >= 4 ? 70 : 40,
            ],
        );
    }

    private function rate(array $metrics, string $key): ?float
    {
        if (! array_key_exists($key, $metrics)) {
            return null;
        }
        $value = (float) $metrics[$key];

        return $value > 1 ? min(1, $value / 100) : max(0, $value);
    }
}
