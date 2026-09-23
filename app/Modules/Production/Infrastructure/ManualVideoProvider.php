<?php

namespace App\Modules\Production\Infrastructure;

use App\Modules\Production\Contracts\VideoProvider;
use App\Modules\Production\DTO\GeneratedMedia;
use App\Modules\Production\DTO\ProviderJob;
use App\Modules\Production\DTO\SceneGenerationRequest;
use App\Modules\Production\Enums\ProviderJobStatus;
use LogicException;

final class ManualVideoProvider implements VideoProvider
{
    public function capabilities(): array
    {
        return ['automated_generation' => false, 'handoff' => true, 'aspect_ratios' => ['9:16']];
    }

    public function submit(SceneGenerationRequest $request): ProviderJob
    {
        return new ProviderJob(
            id: 'manual-'.$request->sceneId,
            provider: 'manual',
            status: ProviderJobStatus::AwaitingManualAction,
            metadata: ['prompt' => $request->prompt, 'negative_prompt' => $request->negativePrompt],
        );
    }

    public function status(string $providerJobId): ProviderJob
    {
        return new ProviderJob($providerJobId, 'manual', ProviderJobStatus::AwaitingManualAction);
    }

    public function retrieve(string $providerJobId): GeneratedMedia
    {
        throw new LogicException('Attach the rendered scene before retrieving a manual provider job.');
    }

    public function cancel(string $providerJobId): void
    {
        // No external work exists to cancel.
    }
}
