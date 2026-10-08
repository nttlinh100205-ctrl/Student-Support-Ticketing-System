<?php

return [
    'url' => env('AUTH_SERVICE_URL', 'http://localhost:8001'),
    'request_url' => env('REQUEST_SERVICE_URL', 'http://localhost:8003'),
    'service' => 'catalog',
    'fake' => env('ACCOUNT_FAKE_AUTH', false) && in_array(env('APP_ENV'), ['local', 'testing'], true),
];
