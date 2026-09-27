<?php

use App\Http\Controllers\AutomaticVideoController;
use App\Http\Controllers\DashboardAutomationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\MediaUploadController;
use App\Http\Controllers\OperatorAuthController;
use App\Http\Controllers\TikTokIntegrationController;
use App\Http\Controllers\TikTokPostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LegalPageController::class, 'home'])->name('home');
Route::get('/privacy-policy', [LegalPageController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalPageController::class, 'terms'])->name('legal.terms');
Route::get('/data-deletion', [LegalPageController::class, 'dataDeletion'])->name('legal.data-deletion');

Route::middleware('guest')->group(function (): void {
    Route::get('/operator/login', [OperatorAuthController::class, 'create'])->name('operator.login');
    Route::post('/operator/login', [OperatorAuthController::class, 'store'])->name('operator.login.store');
});

Route::middleware(['vira.operator.session', 'throttle:60,1'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/automation/run', [DashboardAutomationController::class, 'store'])->name('dashboard.automation.run');
    Route::post('/operator/logout', [OperatorAuthController::class, 'destroy'])->name('operator.logout');
    Route::post('/media', [MediaUploadController::class, 'store'])->name('media.store');
    Route::post('/projects/{contentProject}/generate-video', [AutomaticVideoController::class, 'store'])->name('video.generate');

    Route::get('/integrations/tiktok/connect', [TikTokIntegrationController::class, 'redirect'])->name('tiktok.connect');
    Route::get('/integrations/tiktok/callback', [TikTokIntegrationController::class, 'callback'])->name('tiktok.callback');
    Route::post('/integrations/tiktok/{socialAccount}/refresh', [TikTokIntegrationController::class, 'refresh'])->name('tiktok.refresh');
    Route::delete('/integrations/tiktok/{socialAccount}', [TikTokIntegrationController::class, 'destroy'])->name('tiktok.disconnect');

    Route::post('/tiktok/drafts', [TikTokPostController::class, 'draft'])->name('tiktok.drafts.store');
    Route::post('/tiktok/publish', [TikTokPostController::class, 'publish'])->name('tiktok.publish.store');
    Route::post('/tiktok/posts/{tiktokPost}/refresh', [TikTokPostController::class, 'refresh'])->name('tiktok.posts.refresh');
});
