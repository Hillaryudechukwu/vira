<?php

return [
    'client_key' => env('TIKTOK_CLIENT_KEY'),
    'client_secret' => env('TIKTOK_CLIENT_SECRET'),
    'redirect_uri' => env('TIKTOK_REDIRECT_URI', env('APP_URL').'/integrations/tiktok/callback'),
    'scopes' => array_values(array_filter(array_map('trim', explode(',', env(
        'TIKTOK_SCOPES',
        'user.info.basic,video.upload,video.publish',
    ))))),
    'authorize_url' => 'https://www.tiktok.com/v2/auth/authorize/',
    'api_url' => 'https://open.tiktokapis.com',
    'allowed_media_hosts' => array_values(array_filter(array_map('trim', explode(',', env(
        'TIKTOK_MEDIA_HOSTS',
        (string) parse_url(env('APP_URL', ''), PHP_URL_HOST),
    ))))),
];
