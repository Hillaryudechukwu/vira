<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\PublishApprovedPublication;
use App\Models\AutomationRun;
use App\Models\Channel;
use App\Modules\Approval\Application\DecideApprovalAction;
use App\Modules\Automation\Application\RunGuardedAutomation;
use App\Modules\Publishing\Enums\PublicationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AutomationController extends Controller
{
    public function run(Channel $channel, RunGuardedAutomation $automation): JsonResponse
    {
        return response()->json($automation->execute($channel), 202);
    }

    public function show(AutomationRun $automationRun): JsonResponse
    {
        return response()->json($automationRun->load('contentProject.scripts', 'contentProject.publications.approvalRequest'));
    }

    public function approve(Request $request, AutomationRun $automationRun, DecideApprovalAction $decisions): JsonResponse
    {
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);
        if ($automationRun->status !== 'awaiting_approval') {
            throw ValidationException::withMessages(['run' => 'This run is not awaiting approval.']);
        }

        DB::transaction(function () use ($automationRun, $decisions, $request, $data): void {
            foreach ($automationRun->contentProject->publications()->with('approvalRequest')->get() as $publication) {
                $decisions->approve($publication->approvalRequest, $request->user()?->id, $data['comment'] ?? 'Guarded automation packet reviewed.');
                $publication->update(['status' => PublicationStatus::Queued]);
                PublishApprovedPublication::dispatch($publication->id)->delay($publication->scheduled_for);
            }
            $automationRun->update(['status' => 'scheduled', 'current_stage' => 'publishing']);
        });

        return response()->json($automationRun->fresh()->load('contentProject.publications.approvalRequest'));
    }
}
