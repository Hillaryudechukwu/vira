<?php

namespace App\Modules\Publishing\Application;

use App\Models\SocialAccount;
use App\Modules\Publishing\Infrastructure\TikTok\TikTokApiClient;

final readonly class RefreshTikTokToken
{
    public function __construct(private TikTokApiClient $client) {}

    public function execute(SocialAccount $account): SocialAccount
    {
        if ($account->expires_at === null || $account->expires_at->isAfter(now()->addMinutes(5))) {
            return $account;
        }

        $tokens = $this->client->refresh($account);
        $account->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? $account->refresh_token,
            'scopes' => array_values(array_filter(explode(',', (string) ($tokens['scope'] ?? implode(',', $account->scopes))))),
            'expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 86400)),
            'refresh_expires_at' => now()->addSeconds((int) ($tokens['refresh_expires_in'] ?? 31536000)),
            'status' => 'connected',
        ]);

        return $account->fresh();
    }
}
