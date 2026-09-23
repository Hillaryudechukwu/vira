<?php

namespace App\Modules\BillingOps\Application;

use App\Models\BudgetReservation;
use App\Models\Channel;
use App\Models\ContentProject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReserveBudgetAction
{
    public function execute(Channel $channel, ContentProject $project, string $provider, string $capability, float $amountGbp): BudgetReservation
    {
        if ($amountGbp < 0) {
            throw ValidationException::withMessages(['amount_gbp' => 'A budget reservation cannot be negative.']);
        }

        return DB::transaction(function () use ($channel, $project, $provider, $capability, $amountGbp): BudgetReservation {
            Channel::query()->lockForUpdate()->findOrFail($channel->id);
            $base = BudgetReservation::query()->where('channel_id', $channel->id)->where('status', 'reserved');
            $daily = (clone $base)->where('reserved_at', '>=', now()->startOfDay())->sum('amount_gbp');
            $monthly = (clone $base)->where('reserved_at', '>=', now()->startOfMonth())->sum('amount_gbp');
            $dailyLimit = (float) ($channel->settings['daily_generation_budget_gbp'] ?? config('vira.daily_generation_budget_gbp'));
            $monthlyLimit = (float) ($channel->settings['monthly_budget_gbp'] ?? config('vira.monthly_budget_gbp'));

            if ($daily + $amountGbp > $dailyLimit || $monthly + $amountGbp > $monthlyLimit) {
                throw ValidationException::withMessages(['budget' => 'Generation paused: the configured daily or monthly budget would be exceeded.']);
            }

            return BudgetReservation::query()->create([
                'channel_id' => $channel->id,
                'content_project_id' => $project->id,
                'provider' => $provider,
                'capability' => $capability,
                'amount_gbp' => $amountGbp,
                'status' => 'reserved',
                'reserved_at' => now(),
            ]);
        });
    }
}
