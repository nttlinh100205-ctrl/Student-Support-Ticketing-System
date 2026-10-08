<?php

return [
    'catalog' => env('CATALOG_SERVICE_URL', 'http://localhost:8002'),
    'requests' => env('REQUEST_SERVICE_URL', 'http://localhost:8003'),
    'news' => env('NEWS_SERVICE_URL', 'http://localhost:8004'),
    'reports' => env('REPORT_SERVICE_URL', 'http://localhost:8005'),
];
