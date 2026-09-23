<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name'),
            'guarded_autopilot' => [
                'approval_required' => config('vira.require_approval'),
                'autopilot_enabled' => config('vira.autopilot_enabled'),
                'publisher_driver' => config('vira.publisher_driver'),
            ],
            'time' => now()->toIso8601String(),
        ]);
    }
}
