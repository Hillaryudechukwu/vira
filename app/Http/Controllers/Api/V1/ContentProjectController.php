<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContentProject;
use App\Modules\Editorial\Application\GenerateScriptAction;
use App\Modules\Publishing\Application\PreparePublicationAction;
use App\Modules\Shared\Enums\Platform;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

final class ContentProjectController extends Controller
{
    public function show(ContentProject $contentProject): JsonResponse
    {
        return response()->json($contentProject->load([
            'topicCandidate',
            'scripts' => fn ($query) => $query->orderByDesc('version'),
            'publications.approvalRequest',
        ]));
    }

    public function generateScript(ContentProject $contentProject, GenerateScriptAction $action): JsonResponse
    {
        return response()->json($action->execute($contentProject), 201);
    }

    public function preparePublication(
        Request $request,
        ContentProject $contentProject,
        PreparePublicationAction $action,
    ): JsonResponse {
        $data = $request->validate([
            'platform' => ['required', new Enum(Platform::class)],
            'metadata' => ['required', 'array'],
            'metadata.title' => ['nullable', 'string', 'max:255'],
            'metadata.caption' => ['required', 'string', 'max:5000'],
            'metadata.hashtags' => ['sometimes', 'array', 'max:12'],
            'metadata.hashtags.*' => ['string', 'max:80'],
            'media_checksum' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
            'scheduled_for' => ['required', 'date', 'after:now'],
            'account_reference' => ['sometimes', 'string', 'max:255'],
        ]);

        $publication = $action->execute(
            project: $contentProject,
            platform: Platform::from($data['platform']),
            metadata: $data['metadata'],
            mediaChecksum: $data['media_checksum'],
            scheduledFor: CarbonImmutable::parse($data['scheduled_for'])->utc(),
            accountReference: $data['account_reference'] ?? 'unconnected-account',
        );

        return response()->json($publication, 201);
    }
}
