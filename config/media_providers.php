<?php

return [
    'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-2.5-flare'),
    ],
    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY', env('VOICE_PROVIDER_API_KEY')),
        'voice_id' => env('ELEVENLABS_VOICE_ID'),
        'model_id' => env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2'),
    ],
];
