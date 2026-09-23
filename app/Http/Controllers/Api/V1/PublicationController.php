<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\PublishApprovedPublication;
use App\Models\Publication;
use App\Modules\Publishing\Enums\PublicationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PublicationController extends Controller
{
    public function schedule(Request $request, Publication $publication): JsonResponse
    {
        if ($publication->status !== PublicationStatus::Approved) {
            throw ValidationException::withMessages(['publication' => 'Only approved publications may be scheduled.']);
        }

        $data = $request->validate(['scheduled_for' => ['sometimes', 'date', 'after:now']]);

        if (isset($data['scheduled_for'])) {
            $requested = CarbonImmutable::parse($data['scheduled_for'])->utc();
            $approved = $publication->scheduled_for?->toImmutable()->utc();

            if ($approved === null || ! $requested->equalTo($approved)) {
                throw ValidationException::withMessages([
                    'scheduled_for' => 'Changing the approved schedule requires a new publication and approval packet.',
                ]);
            }
        }

        if ($publication->scheduled_for === null || $publication->scheduled_for->isPast()) {
            throw ValidationException::withMessages(['scheduled_for' => 'The approved publication time must be in the future.']);
        }

        $publication->update(['status' => PublicationStatus::Queued]);

        PublishApprovedPublication::dispatch($publication->id)
            ->delay($publication->scheduled_for);

        return response()->json($publication->fresh());
    }

    public function publish(Publication $publication): JsonResponse
    {
        if (! in_array($publication->status, [PublicationStatus::Approved, PublicationStatus::Queued, PublicationStatus::FailedRetryable], true)) {
            throw ValidationException::withMessages(['publication' => 'Publication is not ready to publish.']);
        }

        PublishApprovedPublication::dispatch($publication->id);

        return response()->json(['queued' => true, 'publication_id' => $publication->id], 202);
    }
}
