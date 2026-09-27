<?php

namespace App\Jobs;

use App\Models\ContentProject;
use App\Models\GenerationRequest;
use App\Modules\Production\Application\GenerateAutomaticVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateAutomaticVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 1800;

    public function __construct(public readonly string $projectId, public readonly string $requestId)
    {
        $this->onQueue('media-render');
    }

    public function handle(GenerateAutomaticVideo $generator): void
    {
        $request = GenerationRequest::query()->findOrFail($this->requestId);
        $request->update([
            'status' => 'running',
            'attempt_count' => $request->attempt_count + 1,
            'started_at' => now(),
            'completed_at' => null,
            'error' => null,
        ]);
        try {
            $generator->execute(ContentProject::query()->findOrFail($this->projectId), $request);
        } catch (\Throwable $exception) {
            $request->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 2000), 'completed_at' => now()]);
            throw $exception;
        }
    }
}
