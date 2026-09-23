<?php

namespace App\Modules\Production\Contracts;

use App\Modules\Production\DTO\GeneratedMedia;
use App\Modules\Production\DTO\ProviderJob;
use App\Modules\Production\DTO\SceneGenerationRequest;

interface VideoProvider
{
    public function capabilities(): array;

    public function submit(SceneGenerationRequest $request): ProviderJob;

    public function status(string $providerJobId): ProviderJob;

    public function retrieve(string $providerJobId): GeneratedMedia;

    public function cancel(string $providerJobId): void;
}
