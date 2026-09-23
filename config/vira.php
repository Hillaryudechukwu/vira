<?php

return [
    'operator_token' => env('VIRA_OPERATOR_TOKEN'),
    'require_approval' => (bool) env('VIRA_REQUIRE_APPROVAL', true),
    'autopilot_enabled' => (bool) env('VIRA_AUTOPILOT_ENABLED', false),
    'max_daily_posts' => (int) env('VIRA_MAX_DAILY_POSTS', 2),
    'daily_generation_budget_gbp' => (float) env('VIRA_DAILY_GENERATION_BUDGET_GBP', 20),
    'monthly_budget_gbp' => (float) env('VIRA_MONTHLY_BUDGET_GBP', 400),
    'estimated_master_cost_gbp' => (float) env('VIRA_ESTIMATED_MASTER_COST_GBP', 0),
    'reasoner_driver' => env('VIRA_REASONER_DRIVER', 'deterministic'),
    'video_driver' => env('VIRA_VIDEO_DRIVER', 'manual'),
    'publisher_driver' => env('VIRA_PUBLISHER_DRIVER', 'null'),
    'topic_score_threshold' => 70.0,
    'topic_score_weights' => [
        'trend_velocity' => 0.15,
        'emotional_resonance' => 0.18,
        'relatability' => 0.14,
        'curiosity_gap' => 0.14,
        'share_intent' => 0.12,
        'comment_potential' => 0.08,
        'channel_fit' => 0.12,
        'novelty' => 0.07,
    ],
];
