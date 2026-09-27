<?php

namespace App\Modules\Publishing\Infrastructure\TikTok;

use App\Models\SocialAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class TikTokApiClient
{
    public function authorizationUrl(string $state): string
    {
        return config('tiktok.authorize_url').'?'.http_build_query([
            'client_key' => config('tiktok.client_key'),
            'response_type' => 'code',
            'scope' => implode(',', config('tiktok.scopes')),
            'redirect_uri' => config('tiktok.redirect_uri'),
            'state' => $state,
        ], encoding_type: PHP_QUERY_RFC3986);
    }

    public function exchangeCode(string $code): array
    {
        return $this->tokenRequest([
            'client_key' => config('tiktok.client_key'),
            'client_secret' => config('tiktok.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('tiktok.redirect_uri'),
        ]);
    }

    public function refresh(SocialAccount $account): array
    {
        return $this->tokenRequest([
            'client_key' => config('tiktok.client_key'),
            'client_secret' => config('tiktok.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ]);
    }

    public function userInfo(string $accessToken): array
    {
        return $this->request($accessToken)
            ->get($this->api('/v2/user/info/'), [
                'fields' => 'open_id,union_id,avatar_url,display_name',
            ])
            ->throw()
            ->json('data.user') ?? [];
    }

    public function creatorInfo(SocialAccount $account): array
    {
        $payload = $this->request($account->access_token)
            ->post($this->api('/v2/post/publish/creator_info/query/'))
            ->throw()
            ->json();

        return $this->data($payload);
    }

    public function uploadDraft(SocialAccount $account, string $videoUrl): array
    {
        $payload = $this->request($account->access_token)
            ->post($this->api('/v2/post/publish/inbox/video/init/'), [
                'source_info' => ['source' => 'PULL_FROM_URL', 'video_url' => $videoUrl],
            ])
            ->throw()
            ->json();

        return $this->data($payload);
    }

    public function directPost(SocialAccount $account, array $postInfo, string $videoUrl): array
    {
        $payload = $this->request($account->access_token)
            ->post($this->api('/v2/post/publish/video/init/'), [
                'post_info' => $postInfo,
                'source_info' => ['source' => 'PULL_FROM_URL', 'video_url' => $videoUrl],
            ])
            ->throw()
            ->json();

        return $this->data($payload);
    }

    public function status(SocialAccount $account, string $publishId): array
    {
        $payload = $this->request($account->access_token)
            ->post($this->api('/v2/post/publish/status/fetch/'), ['publish_id' => $publishId])
            ->throw()
            ->json();

        return $this->data($payload);
    }

    private function tokenRequest(array $form): array
    {
        $response = Http::asForm()
            ->acceptJson()
            ->timeout(30)
            ->post($this->api('/v2/oauth/token/'), $form)
            ->throw()
            ->json();

        if (isset($response['error'])) {
            throw new TikTokApiException(
                message: (string) ($response['error_description'] ?? 'TikTok OAuth request failed.'),
                errorCode: (string) $response['error'],
            );
        }

        return $response;
    }

    private function request(string $accessToken): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken($accessToken)
            ->timeout(45)
            ->retry(2, 500, throw: false);
    }

    private function data(array $payload): array
    {
        $error = $payload['error'] ?? [];
        $code = (string) ($error['code'] ?? 'ok');

        if ($code !== 'ok') {
            throw new TikTokApiException(
                message: (string) ($error['message'] ?? 'TikTok API request failed.'),
                errorCode: $code,
                logId: (string) ($error['log_id'] ?? ''),
                retryable: in_array($code, ['internal_error', 'rate_limit_exceeded'], true),
            );
        }

        return (array) ($payload['data'] ?? []);
    }

    private function api(string $path): string
    {
        return Str::finish((string) config('tiktok.api_url'), '/').ltrim($path, '/');
    }
}
