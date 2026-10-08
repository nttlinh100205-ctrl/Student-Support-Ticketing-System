<?php

return [
    'url' => env('AUTH_SERVICE_URL', 'http://localhost:8001'),
    'catalog_url' => env('CATALOG_SERVICE_URL', 'http://localhost:8002'),
    'facilities_department_code' => env('FACILITIES_DEPARTMENT_CODE', 'CSVC'),
    'service' => 'requests',
    'fake' => env('ACCOUNT_FAKE_AUTH', false) && in_array(env('APP_ENV'), ['local', 'testing'], true),
];
