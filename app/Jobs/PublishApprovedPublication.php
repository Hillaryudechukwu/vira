<?php

namespace App\Jobs;

use App\Models\Publication;
use App\Modules\Approval\Application\ApprovalHashCalculator;
use App\Modules\Approval\Enums\ApprovalStatus;
use App\Modules\Publishing\Contracts\SocialPublisher;
use App\Modules\Publishing\Enums\PublicationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PublishApprovedPublication implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    public function __construct(public readonly string $publicationId)
    {
        $this->onQueue('publishing');
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('publication:'.$this->publicationId))->expireAfter(1000)];
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(SocialPublisher $publisher, ApprovalHashCalculator $hashes): void
    {
        $publication = Publication::query()->with('approvalRequest')->findOrFail($this->publicationId);

        if ($publication->status === PublicationStatus::Published) {
            return;
        }

        $approval = $publication->approvalRequest;
        if ($approval === null || $approval->status !== ApprovalStatus::Approved) {
            throw new RuntimeException('Publication has no valid approval.');
        }

        if (! hash_equals($approval->review_hash, $hashes->calculate($approval->review_payload))) {
            $approval->update(['status' => ApprovalStatus::Invalidated]);
            throw new RuntimeException('Approval hash mismatch.');
        }

        $validation = $publisher->validate($publication);
        if (! ($validation['valid'] ?? false)) {
            $publication->update(['status' => PublicationStatus::FailedTerminal]);
            throw new RuntimeException('Publisher validation failed.');
        }

        $attempt = $publication->attempts()->create([
            'attempt' => $publication->attempts()->count() + 1,
            'stage' => 'publish',
            'response_metadata' => [],
        ]);

        $publication->update(['status' => PublicationStatus::Uploading]);

        try {
            $result = $publisher->publish($publication);

            DB::transaction(function () use ($publication, $attempt, $result): void {
                $attempt->update([
                    'provider_request_id' => $result->externalPostId,
                    'response_metadata' => $result->metadata,
                ]);
                $publication->update([
                    'status' => PublicationStatus::Published,
                    'external_post_id' => $result->externalPostId,
                    'external_url' => $result->externalUrl,
                    'published_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            $attempt->update([
                'retryable' => true,
                'error' => mb_substr($exception->getMessage(), 0, 2000),
                'next_retry_at' => now()->addMinutes(5),
            ]);
            $publication->update(['status' => PublicationStatus::FailedRetryable]);
            throw $exception;
        }
    }
}
