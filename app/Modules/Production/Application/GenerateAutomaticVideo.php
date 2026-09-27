<?php

namespace App\Modules\Production\Application;

use App\Models\ContentProject;
use App\Models\GenerationRequest;
use App\Models\MediaAsset;
use App\Modules\Production\Infrastructure\ElevenLabsVoiceGenerator;
use App\Modules\Production\Infrastructure\OpenAiImageGenerator;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class GenerateAutomaticVideo
{
    public function __construct(private OpenAiImageGenerator $images, private ElevenLabsVoiceGenerator $voice, private SrtCaptionBuilder $captions, private FfmpegComposer $composer) {}

    public function execute(ContentProject $project, GenerationRequest $request): MediaAsset
    {
        $script = $project->scripts()->latest('version')->firstOrFail();
        $beats = $script->structure['beats'] ?? [];
        if ($beats === []) {
            throw new RuntimeException('The current script has no storyboard beats.');
        }
        $directory = public_path('media/generated/'.$project->id.'/'.Str::uuid());
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $scenePaths = [];
        foreach ($beats as $index => $beat) {
            $prompt = 'Cinematic psychological realism, vertical 9:16, charcoal and muted blue palette, warm amber only for healthy contrast, ordinary modern flat, partial or obscured faces, no text, logos, violence or distorted anatomy. Scene: '.($beat['visual_intent'] ?? $beat['narration']);
            $path = $directory.'/scene-'.($index + 1).'.png';
            file_put_contents($path, $this->images->generate($prompt));
            $scenePaths[] = $path;
        }
        $voicePath = $directory.'/voice.mp3';
        file_put_contents($voicePath, $this->voice->synthesize($script->narration));
        $captionPath = $directory.'/captions.srt';
        file_put_contents($captionPath, $this->captions->build($beats));
        $output = $directory.'/master.mp4';
        $this->composer->composeSlideshow($scenePaths, array_map(fn (array $beat): int => (int) $beat['end_ms'] - (int) $beat['start_ms'], $beats), $voicePath, $captionPath, $output);

        $asset = MediaAsset::query()->create(['content_project_id' => $project->id, 'generation_request_id' => $request->id, 'asset_type' => 'master', 'disk' => 'public', 'object_key' => str($output)->after(public_path().'/')->toString(), 'mime_type' => 'video/mp4', 'byte_size' => filesize($output), 'checksum' => hash_file('sha256', $output), 'width' => 1080, 'height' => 1920, 'duration_ms' => $script->estimated_duration_ms, 'frame_rate' => 30, 'provenance' => ['openai_model' => config('media_providers.openai.image_model'), 'elevenlabs_voice_id' => config('media_providers.elevenlabs.voice_id'), 'script_version' => $script->version], 'rights_status' => 'provider_terms_reviewed']);
        $request->update(['status' => 'completed', 'completed_at' => now()]);

        return $asset;
    }
}
