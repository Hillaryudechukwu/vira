<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Modules\Analytics\Application\AnalysePerformanceAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AnalyticsController extends Controller
{
    public function store(Request $request, Publication $publication, AnalysePerformanceAction $action): JsonResponse
    {
        $data = $request->validate([
            'window' => ['required', 'in:30m,2h,24h,7d,manual'],
            'metrics' => ['required', 'array'],
            'metrics.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        return response()->json($action->execute($publication, $data['metrics'], $data['window']), 201);
    }

    public function show(Publication $publication): JsonResponse
    {
        return response()->json($publication->load('metricSnapshots', 'performanceReport'));
    }
}
