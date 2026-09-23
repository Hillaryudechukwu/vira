<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\AutomationController;
use App\Http\Controllers\Api\V1\ContentProjectController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PublicationController;
use App\Http\Controllers\Api\V1\TopicCandidateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class);

    Route::middleware('vira.operator')->group(function (): void {
        Route::get('/topics', [TopicCandidateController::class, 'index']);
        Route::post('/topics', [TopicCandidateController::class, 'store']);
        Route::post('/topics/{topicCandidate}/select', [TopicCandidateController::class, 'select']);

        Route::get('/projects/{contentProject}', [ContentProjectController::class, 'show']);
        Route::post('/projects/{contentProject}/generate-script', [ContentProjectController::class, 'generateScript']);
        Route::post('/projects/{contentProject}/prepare-publication', [ContentProjectController::class, 'preparePublication']);

        Route::post('/approvals/{approvalRequest}/approve', [ApprovalController::class, 'approve']);
        Route::post('/approvals/{approvalRequest}/reject', [ApprovalController::class, 'reject']);

        Route::post('/publications/{publication}/schedule', [PublicationController::class, 'schedule']);
        Route::post('/publications/{publication}/publish', [PublicationController::class, 'publish']);
        Route::post('/channels/{channel}/automation/run', [AutomationController::class, 'run']);
        Route::get('/automation/{automationRun}', [AutomationController::class, 'show']);
        Route::post('/automation/{automationRun}/approve', [AutomationController::class, 'approve']);
        Route::post('/analytics/publications/{publication}/snapshots', [AnalyticsController::class, 'store']);
        Route::get('/analytics/publications/{publication}', [AnalyticsController::class, 'show']);
    });
});
