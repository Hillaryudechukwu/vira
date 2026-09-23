<?php

namespace App\Modules\Publishing\Application;

use App\Models\ApprovalRequest;
use App\Models\ContentProject;
use App\Models\Publication;
use App\Modules\Approval\Application\ApprovalHashCalculator;
use App\Modules\Approval\Enums\ApprovalStatus;
use App\Modules\Editorial\Enums\ContentProjectStatus;
use App\Modules\Publishing\Enums\PublicationStatus;
use App\Modules\Shared\Enums\Platform;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class PreparePublicationAction
{
    public function __construct(
        private ApprovalHashCalculator $hashes,
        private PublicationIdempotencyKey $idempotency,
    ) {}

    public function execute(
        ContentProject $project,
        Platform $platform,
        array $metadata,
        string $mediaChecksum,
        CarbonImmutable $scheduledFor,
        string $accountReference = 'unconnected-account',
    ): Publication {
        $script = $project->scripts()->latest('version')->first();

        if ($script === null) {
            throw ValidationException::withMessages(['project' => 'Generate a script before preparing publication.']);
        }

        if (! preg_match('/^[a-f0-9]{64}$/', $mediaChecksum)) {
            throw ValidationException::withMessages(['media_checksum' => 'Media checksum must be a SHA-256 hex digest.']);
        }

        $reviewPayload = [
            'project_id' => $project->id,
            'script_id' => $script->id,
            'script_version' => $script->version,
            'narration' => $script->narration,
            'claims' => $script->claims,
            'platform' => $platform->value,
            'metadata' => $metadata,
            'media_checksum' => $mediaChecksum,
            'scheduled_for' => $scheduledFor->toIso8601String(),
        ];

        return DB::transaction(function () use (
            $project,
            $platform,
            $metadata,
            $mediaChecksum,
            $scheduledFor,
            $accountReference,
            $reviewPayload,
        ): Publication {
            $publication = Publication::query()->create([
                'content_project_id' => $project->id,
                'platform' => $platform,
                'status' => PublicationStatus::AwaitingApproval,
                'metadata' => $metadata,
                'media_checksum' => $mediaChecksum,
                'metadata_version' => 1,
                'idempotency_key' => $this->idempotency->make(
                    platform: $platform,
                    accountId: $accountReference,
                    projectId: $project->id,
                    mediaChecksum: $mediaChecksum,
                    metadataVersion: 1,
                    scheduledFor: $scheduledFor,
                ),
                'scheduled_for' => $scheduledFor,
                'timezone_snapshot' => $project->channel->default_timezone,
            ]);

            ApprovalRequest::query()->create([
                'publication_id' => $publication->id,
                'status' => ApprovalStatus::Pending,
                'review_hash' => $this->hashes->calculate($reviewPayload),
                'review_payload' => $reviewPayload,
                'expires_at' => now()->addDays(7),
            ]);

            $project->update(['status' => ContentProjectStatus::AwaitingApproval]);

            return $publication->load('approvalRequest');
        });
    }
}
