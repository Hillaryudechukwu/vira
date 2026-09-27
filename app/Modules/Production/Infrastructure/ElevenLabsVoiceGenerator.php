<?php

namespace App\Modules\Production\Infrastructure;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class ElevenLabsVoiceGenerator
{
    public function synthesize(string $text): string
    {
        $voiceId = (string) config('media_providers.elevenlabs.voice_id');
        if ($voiceId === '' || in_array(strtolower($voiceId), ['your_voice_id', 'replace-me', 'changeme'], true)) {
            throw new RuntimeException('ELEVENLABS_VOICE_ID must contain a real voice ID from your ElevenLabs account, not the example placeholder.');
        }

        return Http::withHeaders(['xi-api-key' => (string) config('media_providers.elevenlabs.api_key'), 'Accept' => 'audio/mpeg'])
            ->asJson()->timeout(180)
            ->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}?output_format=mp3_44100_128", [
                'text' => $text,
                'model_id' => config('media_providers.elevenlabs.model_id'),
                'voice_settings' => ['stability' => 0.65, 'similarity_boost' => 0.75, 'style' => 0.15, 'use_speaker_boost' => true],
            ])->throw()->body();
    }
}
