<?php

use App\Jobs\PublishApprovedPublication;
use App\Models\Publication;
use App\Modules\Publishing\Enums\PublicationStatus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {
    Publication::query()
        ->where('status', PublicationStatus::Queued->value)
        ->where('scheduled_for', '<=', now())
        ->each(fn (Publication $publication) => PublishApprovedPublication::dispatch($publication->id));
})->everyMinute()->name('vira:dispatch-due-publications')->withoutOverlapping();

Artisan::command('vira:status', function (): void {
    $this->table(
        ['Setting', 'Value'],
        [
            ['Approval required', config('vira.require_approval') ? 'yes' : 'no'],
            ['Autopilot', config('vira.autopilot_enabled') ? 'enabled' : 'disabled'],
            ['Reasoner', config('vira.reasoner_driver')],
            ['Video provider', config('vira.video_driver')],
            ['Publisher', config('vira.publisher_driver')],
        ],
    );
})->purpose('Show the current VIRA operating configuration');
