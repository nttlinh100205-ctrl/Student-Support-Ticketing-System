<?php

return [

    'algorithm' => env('JWT_ALGORITHM', 'RS256'),

    'ttl' => (int) env('JWT_TTL', 900),

    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 604800),

    'private_key_path' => env(
        'JWT_PRIVATE_KEY_PATH',
        'storage/app/jwt/private.pem'
    ),

    'public_key_path' => env(
        'JWT_PUBLIC_KEY_PATH',
        'storage/app/jwt/public.pem'
    ),

];