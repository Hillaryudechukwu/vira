<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAutomaticVideoJob;
use App\Models\ContentProject;
use App\Models\GenerationRequest;
use App\Modules\BillingOps\Application\ReserveBudgetAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

final class AutomaticVideoController extends Controller
{
    public function store(ContentProject $contentProject, ReserveBudgetAction $budgets): RedirectResponse
    {
        $voiceId = (string) config('media_providers.elevenlabs.voice_id');
        if (blank(config('media_providers.openai.api_key')) || blank(config('media_providers.elevenlabs.api_key')) || blank($voiceId) || in_array(strtolower($voiceId), ['your_voice_id', 'replace-me', 'changeme'], true)) {
            throw ValidationException::withMessages(['generation' => 'OpenAI and ElevenLabs credentials and an ElevenLabs voice ID are required.']);
        }
        try {
            $ffmpeg = new Process([config('media_providers.ffmpeg_binary', 'ffmpeg'), '-version']);
            $ffmpeg->run();
            if (! $ffmpeg->isSuccessful()) {
                throw new \RuntimeException;
            }
        } catch (\Throwable) {
            throw ValidationException::withMessages(['generation' => 'FFmpeg is unavailable on this worker. Configure a media worker before generating video.']);
        }
        if ($contentProject->scripts()->doesntExist()) {
            throw ValidationException::withMessages(['generation' => 'Generate a script before generating video.']);
        }
        $hash = hash('sha256', implode('|', [$contentProject->id, $contentProject->current_script_version, config('media_providers.openai.image_model'), config('media_providers.elevenlabs.voice_id')]));
        $request = GenerationRequest::query()->firstOrCreate(['request_hash' => $hash], ['content_project_id' => $contentProject->id, 'provider' => 'openai+elevenlabs', 'capability' => 'automatic_video', 'input' => ['script_version' => $contentProject->current_script_version], 'status' => 'pending', 'estimated_cost_gbp' => 0]);
        if (! $request->wasRecentlyCreated && in_array($request->status, ['pending', 'running', 'completed'], true)) {
            return back()->with('status', 'This script version is already queued or generated.');
        }
        $budgets->execute($contentProject->channel, $contentProject, 'openai+elevenlabs', 'automatic_video', 0);
        $request->update(['status' => 'pending', 'error' => null, 'completed_at' => null]);
        GenerateAutomaticVideoJob::dispatch($contentProject->id, $request->id);

        return back()->with('status', 'Automatic video generation queued. Refresh the dashboard to see progress.');
    }
}
