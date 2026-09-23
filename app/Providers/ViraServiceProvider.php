<?php

namespace App\Providers;

use App\Modules\Editorial\Contracts\StructuredReasoner;
use App\Modules\Editorial\Infrastructure\DeterministicReasoner;
use App\Modules\Production\Contracts\VideoProvider;
use App\Modules\Production\Infrastructure\ManualVideoProvider;
use App\Modules\Publishing\Contracts\SocialPublisher;
use App\Modules\Publishing\Infrastructure\NullSocialPublisher;
use Illuminate\Support\ServiceProvider;

final class ViraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StructuredReasoner::class, DeterministicReasoner::class);
        $this->app->bind(SocialPublisher::class, NullSocialPublisher::class);
        $this->app->bind(VideoProvider::class, ManualVideoProvider::class);
    }
}
