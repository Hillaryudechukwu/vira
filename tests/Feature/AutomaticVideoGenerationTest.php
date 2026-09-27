<?php

namespace Tests\Feature;

use App\Jobs\GenerateAutomaticVideoJob;
use App\Models\Channel;
use App\Models\ContentProject;
use App\Models\Script;
use App\Models\TopicCandidate;
use App\Modules\Production\Infrastructure\ElevenLabsVoiceGenerator;
use App\Modules\Production\Infrastructure\OpenAiImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AutomaticVideoGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_is_queued_for_a_scripted_project(): void
    {
        Bus::fake();
        config()->set('media_providers.openai.api_key', 'openai-key');
        config()->set('media_providers.elevenlabs.api_key', 'eleven-key');
        config()->set('media_providers.elevenlabs.voice_id', 'voice-id');
        config()->set('media_providers.ffmpeg_binary', '/usr/bin/true');
        $project = $this->project();

        $this->withSession(['vira_operator_authenticated' => true])->post("/projects/{$project->id}/generate-video")->assertRedirect();

        $this->assertDatabaseHas('generation_requests', ['content_project_id' => $project->id, 'capability' => 'automatic_video', 'status' => 'pending']);
        Bus::assertDispatched(GenerateAutomaticVideoJob::class);
    }

    public function test_openai_and_elevenlabs_provider_contracts(): void
    {
        config()->set('media_providers.openai.api_key', 'openai-key');
        config()->set('media_providers.openai.image_model', 'gpt-image-2.5-flare');
        config()->set('media_providers.elevenlabs.api_key', 'eleven-key');
        config()->set('media_providers.elevenlabs.voice_id', 'voice-id');
        Http::fake([
            'api.openai.com/v1/images/generations' => Http::response(['data' => [['b64_json' => base64_encode('png-bytes')]]]),
            'api.elevenlabs.io/v1/text-to-speech/*' => Http::response('mp3-bytes', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        self::assertSame('png-bytes', app(OpenAiImageGenerator::class)->generate('scene'));
        self::assertSame('mp3-bytes', app(ElevenLabsVoiceGenerator::class)->synthesize('narration'));
    }

    private function project(): ContentProject
    {
        $channel = Channel::query()->create(['name' => 'Auto', 'slug' => 'auto', 'niche' => 'Education', 'settings' => []]);
        $topic = TopicCandidate::query()->create(['channel_id' => $channel->id, 'title' => 'Topic', 'angle' => 'Angle', 'content_pillar' => 'test', 'status' => 'selected', 'component_scores' => [], 'viral_score' => 80]);
        $project = ContentProject::query()->create(['channel_id' => $channel->id, 'topic_candidate_id' => $topic->id, 'working_title' => 'Automatic video', 'objective' => 'Generate', 'status' => 'producing', 'current_script_version' => 1]);
        Script::query()->create(['content_project_id' => $project->id, 'version' => 1, 'narration' => 'A complete narration.', 'word_count' => 3, 'estimated_duration_ms' => 3000, 'structure' => ['beats' => [['start_ms' => 0, 'end_ms' => 3000, 'narration' => 'A complete narration.', 'caption' => 'COMPLETE']]], 'claims' => [], 'model_metadata' => [], 'prompt_version' => 'test', 'status' => 'draft']);

        return $project;
    }
}
