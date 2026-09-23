<?php

namespace App\Modules\Publishing\Contracts;

use App\Models\Publication;
use App\Modules\Publishing\DTO\PublishResult;

interface SocialPublisher
{
    public function validate(Publication $publication): array;

    public function publish(Publication $publication): PublishResult;
}
