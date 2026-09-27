<?php

namespace App\Modules\Production\Infrastructure;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OpenAiImageGenerator
{
    public function generate(string $prompt): string
    {
        $response = Http::withToken((string) config('media_providers.openai.api_key'))
            ->acceptJson()->asJson()->timeout(180)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => config('media_providers.openai.image_model'),
                'prompt' => $prompt,
                'size' => '1024x1536',
                'quality' => 'medium',
                'output_format' => 'png',
            ])->throw()->json('data.0.b64_json');

        $bytes = is_string($response) ? base64_decode($response, true) : false;
        if ($bytes === false) {
            throw new RuntimeException('OpenAI did not return a valid generated image.');
        }

        return $bytes;
    }
}
