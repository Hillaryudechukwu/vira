<?php

namespace App\Modules\Publishing\Infrastructure;

use App\Models\Publication;
use App\Modules\Publishing\Contracts\SocialPublisher;
use App\Modules\Publishing\DTO\PublishResult;

final class NullSocialPublisher implements SocialPublisher
{
    public function validate(Publication $publication): array
    {
        return [
            'valid' => true,
            'warnings' => ['Null publisher is active; no external social platform will be modified.'],
        ];
    }

    public function publish(Publication $publication): PublishResult
    {
        return new PublishResult(
            externalPostId: 'local-'.$publication->id,
            externalUrl: null,
            status: 'published',
            metadata: ['driver' => 'null', 'external_side_effect' => false],
        );
    }
}
