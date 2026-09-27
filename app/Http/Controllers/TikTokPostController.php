<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\TiktokPost;
use App\Modules\Publishing\Application\RefreshTikTokToken;
use App\Modules\Publishing\Infrastructure\TikTok\TikTokApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class TikTokPostController extends Controller
{
    public function draft(Request $request, TikTokApiClient $client, RefreshTikTokToken $tokens): RedirectResponse
    {
        $data = $this->validateCommon($request);
        $account = $tokens->execute($this->account($data['social_account_id'], 'video.upload'));
        $post = TiktokPost::query()->create([
            'social_account_id' => $account->id,
            'mode' => 'draft',
            'status' => 'initialising',
            'video_url' => $data['video_url'],
            'caption' => $data['caption'] ?? null,
            'is_aigc' => $request->boolean('is_aigc'),
            'consent_confirmed' => true,
            'consented_at' => now(),
            'request_payload' => ['source' => 'PULL_FROM_URL', 'video_url' => $data['video_url']],
        ]);

        try {
            $result = $client->uploadDraft($account, $data['video_url']);
            $post->update(['status' => 'submitted', 'publish_id' => $result['publish_id'] ?? null, 'response_payload' => $result, 'submitted_at' => now()]);
        } catch (\Throwable $exception) {
            $post->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 2000)]);
            throw $exception;
        }

        return back()->with('status', 'Video sent to TikTok. The creator should continue from the TikTok inbox notification.');
    }

    public function publish(Request $request, TikTokApiClient $client, RefreshTikTokToken $tokens): RedirectResponse
    {
        $data = $request->validate([
            ...$this->commonRules(),
            'privacy_level' => ['required', 'string', Rule::in(['PUBLIC_TO_EVERYONE', 'FOLLOWER_OF_CREATOR', 'MUTUAL_FOLLOW_FRIENDS', 'SELF_ONLY'])],
            'consent' => ['accepted'],
            'disable_comment' => ['sometimes', 'boolean'],
            'disable_duet' => ['sometimes', 'boolean'],
            'disable_stitch' => ['sometimes', 'boolean'],
        ]);
        $account = $tokens->execute($this->account($data['social_account_id'], 'video.publish'));
        $creator = $client->creatorInfo($account);
        $allowedPrivacy = (array) ($creator['privacy_level_options'] ?? []);
        if (! in_array($data['privacy_level'], $allowedPrivacy, true)) {
            throw ValidationException::withMessages(['privacy_level' => 'TikTok does not currently allow that privacy level for this creator.']);
        }

        $postInfo = [
            'title' => $data['caption'] ?? '',
            'privacy_level' => $data['privacy_level'],
            'disable_comment' => (bool) ($creator['comment_disabled'] ?? false) || $request->boolean('disable_comment'),
            'disable_duet' => (bool) ($creator['duet_disabled'] ?? false) || $request->boolean('disable_duet'),
            'disable_stitch' => (bool) ($creator['stitch_disabled'] ?? false) || $request->boolean('disable_stitch'),
            'video_cover_timestamp_ms' => 1000,
            'brand_content_toggle' => false,
            'brand_organic_toggle' => false,
            'is_aigc' => $request->boolean('is_aigc'),
        ];
        $post = TiktokPost::query()->create([
            'social_account_id' => $account->id,
            'mode' => 'direct',
            'status' => 'initialising',
            'video_url' => $data['video_url'],
            'caption' => $data['caption'] ?? null,
            'privacy_level' => $data['privacy_level'],
            'disable_comment' => $postInfo['disable_comment'],
            'disable_duet' => $postInfo['disable_duet'],
            'disable_stitch' => $postInfo['disable_stitch'],
            'is_aigc' => $postInfo['is_aigc'],
            'consent_confirmed' => true,
            'consented_at' => now(),
            'request_payload' => ['post_info' => $postInfo, 'source_info' => ['source' => 'PULL_FROM_URL', 'video_url' => $data['video_url']]],
        ]);

        try {
            $result = $client->directPost($account, $postInfo, $data['video_url']);
            $post->update(['status' => 'submitted', 'publish_id' => $result['publish_id'] ?? null, 'response_payload' => $result, 'submitted_at' => now()]);
        } catch (\Throwable $exception) {
            $post->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 2000)]);
            throw $exception;
        }

        return back()->with('status', 'TikTok Direct Post submitted. VIRA will track processing status.');
    }

    public function refresh(TiktokPost $tiktokPost, TikTokApiClient $client, RefreshTikTokToken $tokens): RedirectResponse
    {
        if ($tiktokPost->publish_id === null) {
            throw ValidationException::withMessages(['post' => 'This post has no TikTok publish identifier.']);
        }
        $account = $tokens->execute($tiktokPost->socialAccount);
        $result = $client->status($account, $tiktokPost->publish_id);
        $tiktokPost->update($this->statusUpdate($result));

        return back()->with('status', 'TikTok status refreshed.');
    }

    public function statusUpdate(array $result): array
    {
        $remote = (string) ($result['status'] ?? 'PROCESSING');
        $status = match ($remote) {
            'PUBLISH_COMPLETE', 'SEND_TO_USER_INBOX' => 'complete',
            'FAILED' => 'failed',
            default => 'processing',
        };

        return ['status' => $status, 'response_payload' => $result, 'error' => $status === 'failed' ? json_encode($result['fail_reason'] ?? 'TikTok processing failed.') : null, 'completed_at' => $status === 'complete' ? now() : null];
    }

    private function validateCommon(Request $request): array
    {
        return $request->validate($this->commonRules());
    }

    private function commonRules(): array
    {
        return [
            'social_account_id' => ['required', 'uuid', 'exists:social_accounts,id'],
            'video_url' => [
                'required', 'url:https', 'max:2000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $host = mb_strtolower((string) parse_url((string) $value, PHP_URL_HOST));
                    $allowed = array_map('mb_strtolower', config('tiktok.allowed_media_hosts', []));
                    if ($host === '' || ! in_array($host, $allowed, true)) {
                        $fail('The video must use a TikTok-verified VIRA media domain.');
                    }
                },
            ],
            'caption' => ['nullable', 'string', 'max:2200'],
            'is_aigc' => ['sometimes', 'boolean'],
            'rights_confirmed' => ['accepted'],
        ];
    }

    private function account(string $id, string $scope): SocialAccount
    {
        $account = SocialAccount::query()->findOrFail($id);
        if ($account->platform !== 'tiktok' || $account->status !== 'connected') {
            abort(422, 'A connected TikTok account is required.');
        }
        if (! in_array($scope, $account->scopes, true)) {
            throw ValidationException::withMessages(['scope' => "TikTok did not grant the {$scope} scope."]);
        }

        return $account;
    }
}
