<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ContentProject;
use App\Models\GenerationRequest;
use App\Models\MediaAsset;
use App\Models\SocialAccount;
use App\Models\TiktokPost;
use App\Modules\Publishing\Application\RefreshTikTokToken;
use App\Modules\Publishing\Infrastructure\TikTok\TikTokApiClient;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(TikTokApiClient $client, RefreshTikTokToken $tokens): View
    {
        $voiceId = (string) config('media_providers.elevenlabs.voice_id');
        $account = SocialAccount::query()->where('platform', 'tiktok')->latest()->first();
        $creatorError = null;
        if ($account?->status === 'connected' && in_array('video.publish', $account->scopes, true)) {
            try {
                $account = $tokens->execute($account);
                $creator = $client->creatorInfo($account);
                $account->update(['metadata' => array_merge($account->metadata ?? [], ['creator_info' => $creator]), 'last_verified_at' => now()]);
                $account->refresh();
            } catch (\Throwable $exception) {
                $creatorError = $exception->getMessage();
            }
        }

        return view('dashboard', [
            'tiktokAccount' => $account,
            'creatorError' => $creatorError,
            'projects' => ContentProject::query()->with(['scripts' => fn ($query) => $query->latest('version')])->latest()->limit(10)->get(),
            'channels' => Channel::query()->orderBy('name')->get(),
            'mediaAssets' => MediaAsset::query()->where('disk', 'public')->whereIn('asset_type', ['master', 'platform_variant'])->latest()->limit(20)->get(),
            'generationRequests' => GenerationRequest::query()->where('capability', 'automatic_video')->latest()->limit(10)->get(),
            'automaticGenerationConfigured' => filled(config('media_providers.openai.api_key')) && filled(config('media_providers.elevenlabs.api_key')) && filled($voiceId) && ! in_array(strtolower($voiceId), ['your_voice_id', 'replace-me', 'changeme'], true),
            'tiktokPosts' => TiktokPost::query()->with('socialAccount')->latest()->limit(20)->get(),
            'tiktokConfigured' => filled(config('tiktok.client_key')) && filled(config('tiktok.client_secret')),
        ]);
    }
}
