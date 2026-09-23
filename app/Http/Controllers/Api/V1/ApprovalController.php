<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Modules\Approval\Application\DecideApprovalAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApprovalController extends Controller
{
    public function approve(
        Request $request,
        ApprovalRequest $approvalRequest,
        DecideApprovalAction $action,
    ): JsonResponse {
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);

        return response()->json($action->approve(
            approval: $approvalRequest,
            actorId: $request->user()?->id,
            comment: $data['comment'] ?? null,
        ));
    }

    public function reject(
        Request $request,
        ApprovalRequest $approvalRequest,
        DecideApprovalAction $action,
    ): JsonResponse {
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']]);

        return response()->json($action->reject(
            approval: $approvalRequest,
            actorId: $request->user()?->id,
            comment: $data['comment'],
        ));
    }
}
