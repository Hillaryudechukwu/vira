<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\SocialAccount;
use App\Modules\Publishing\Infrastructure\TikTok\TikTokApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class TikTokIntegrationController extends Controller
{
    public function redirect(Request $request, TikTokApiClient $client): RedirectResponse
    {
        if (blank(config('tiktok.client_key')) || blank(config('tiktok.client_secret'))) {
            throw ValidationException::withMessages(['tiktok' => 'TikTok client credentials are not configured.']);
        }

        $state = Str::random(64);
        $request->session()->put('tiktok_oauth_state', $state);

        return redirect()->away($client->authorizationUrl($state));
    }

    public function callback(Request $request, TikTokApiClient $client): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required_without:error', 'string'],
            'state' => ['required', 'string'],
            'error' => ['nullable', 'string'],
            'error_description' => ['nullable', 'string'],
        ]);

        $expectedState = (string) $request->session()->pull('tiktok_oauth_state');
        if ($expectedState === '' || ! hash_equals($expectedState, $data['state'])) {
            throw ValidationException::withMessages(['state' => 'TikTok OAuth state validation failed.']);
        }
        if (isset($data['error'])) {
            return redirect()->route('dashboard')->withErrors(['tiktok' => $data['error_description'] ?? $data['error']]);
        }

        $tokens = $client->exchangeCode($data['code']);
        $profile = $client->userInfo($tokens['access_token']);
        $openId = (string) ($tokens['open_id'] ?? $profile['open_id'] ?? '');
        if ($openId === '') {
            throw ValidationException::withMessages(['tiktok' => 'TikTok did not return an account identifier.']);
        }

        SocialAccount::query()->updateOrCreate(
            ['platform' => 'tiktok', 'external_account_id' => $openId],
            [
                'channel_id' => Channel::query()->value('id'),
                'display_name' => $profile['display_name'] ?? null,
                'avatar_url' => $profile['avatar_url'] ?? null,
                'status' => 'connected',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'scopes' => array_values(array_filter(explode(',', (string) ($tokens['scope'] ?? '')))),
                'expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 86400)),
                'refresh_expires_at' => now()->addSeconds((int) ($tokens['refresh_expires_in'] ?? 31536000)),
                'last_verified_at' => now(),
                'metadata' => ['union_id' => $profile['union_id'] ?? null],
            ],
        );

        return redirect()->route('dashboard')->with('status', 'TikTok account connected successfully.');
    }

    public function refresh(SocialAccount $socialAccount, TikTokApiClient $client): RedirectResponse
    {
        abort_unless($socialAccount->platform === 'tiktok', 404);
        $creator = $client->creatorInfo($socialAccount);
        $socialAccount->update([
            'display_name' => $creator['creator_nickname'] ?? $socialAccount->display_name,
            'avatar_url' => $creator['creator_avatar_url'] ?? $socialAccount->avatar_url,
            'metadata' => array_merge($socialAccount->metadata ?? [], ['creator_info' => $creator]),
            'last_verified_at' => now(),
        ]);

        return back()->with('status', 'TikTok creator permissions refreshed.');
    }

    public function destroy(SocialAccount $socialAccount): RedirectResponse
    {
        abort_unless($socialAccount->platform === 'tiktok', 404);
        $socialAccount->update(['status' => 'revoked', 'access_token' => '', 'refresh_token' => null]);

        return back()->with('status', 'TikTok account disconnected locally. Revoke VIRA in TikTok settings to invalidate the remote grant.');
    }
}
