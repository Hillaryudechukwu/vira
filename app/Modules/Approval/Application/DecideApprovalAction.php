<?php

namespace App\Modules\Approval\Application;

use App\Models\ApprovalRequest;
use App\Modules\Approval\Enums\ApprovalStatus;
use App\Modules\Publishing\Enums\PublicationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DecideApprovalAction
{
    public function __construct(private ApprovalHashCalculator $hashes) {}

    public function approve(ApprovalRequest $approval, ?int $actorId, ?string $comment = null): ApprovalRequest
    {
        $this->assertPendingAndCurrent($approval);

        return DB::transaction(function () use ($approval, $actorId, $comment): ApprovalRequest {
            $approval->update([
                'status' => ApprovalStatus::Approved,
                'decided_by' => $actorId,
                'decided_at' => now(),
                'decision_comment' => $comment,
            ]);

            $approval->publication()->update(['status' => PublicationStatus::Approved]);

            return $approval->fresh('publication');
        });
    }

    public function reject(ApprovalRequest $approval, ?int $actorId, string $comment): ApprovalRequest
    {
        if ($approval->status !== ApprovalStatus::Pending) {
            throw ValidationException::withMessages(['approval' => 'Only pending approvals can be rejected.']);
        }

        return DB::transaction(function () use ($approval, $actorId, $comment): ApprovalRequest {
            $approval->update([
                'status' => ApprovalStatus::Rejected,
                'decided_by' => $actorId,
                'decided_at' => now(),
                'decision_comment' => $comment,
            ]);

            $approval->publication()->update(['status' => PublicationStatus::Cancelled]);

            return $approval->fresh('publication');
        });
    }

    private function assertPendingAndCurrent(ApprovalRequest $approval): void
    {
        if ($approval->status !== ApprovalStatus::Pending) {
            throw ValidationException::withMessages(['approval' => 'Only pending approvals can be approved.']);
        }

        if ($approval->expires_at?->isPast()) {
            $approval->update(['status' => ApprovalStatus::Expired]);
            throw ValidationException::withMessages(['approval' => 'This approval request has expired.']);
        }

        if (! hash_equals($approval->review_hash, $this->hashes->calculate($approval->review_payload))) {
            $approval->update(['status' => ApprovalStatus::Invalidated]);
            throw ValidationException::withMessages(['approval' => 'The reviewed content changed and must be approved again.']);
        }
    }
}
