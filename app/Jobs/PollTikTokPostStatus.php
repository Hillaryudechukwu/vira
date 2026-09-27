<?php

namespace App\Jobs;

use App\Http\Controllers\TikTokPostController;
use App\Models\TiktokPost;
use App\Modules\Publishing\Application\RefreshTikTokToken;
use App\Modules\Publishing\Infrastructure\TikTok\TikTokApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class PollTikTokPostStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public readonly string $postId)
    {
        $this->onQueue('publishing');
    }

    public function backoff(): array
    {
        return [30, 60, 120, 300];
    }

    public function handle(TikTokApiClient $client, RefreshTikTokToken $tokens, TikTokPostController $controller): void
    {
        $post = TiktokPost::query()->with('socialAccount')->findOrFail($this->postId);
        if (! in_array($post->status, ['submitted', 'processing'], true) || $post->publish_id === null) {
            return;
        }
        $account = $tokens->execute($post->socialAccount);
        $post->update($controller->statusUpdate($client->status($account, $post->publish_id)));
    }
}
