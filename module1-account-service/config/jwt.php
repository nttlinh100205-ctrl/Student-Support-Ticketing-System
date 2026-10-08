<?php

return [
    'private_key_path' => env(
        'JWT_PRIVATE_KEY_PATH',
        'storage/app/jwt/private.pem'
    ),

    'public_key_path' => env(
        'JWT_PUBLIC_KEY_PATH',
        'storage/app/jwt/public.pem'
    ),

    'algorithm' => env('JWT_ALGORITHM', 'RS256'),

    'ttl' => (int) env('JWT_TTL', 3600),
];
