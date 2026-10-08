<?php

/*
|--------------------------------------------------------------------------
| BẢNG ĐIỀU HƯỚNG CỦA API GATEWAY
|--------------------------------------------------------------------------
|
| Tiền tố URL => địa chỉ service xử lý. Gateway chọn tiền tố dài nhất khớp
| với đường dẫn, rồi chuyển nguyên request (method, query, body, header)
| sang service đó. Đổi địa chỉ service bằng biến môi trường, ví dụ:
|   ORG_SERVICE_URL=http://127.0.0.1:9002 php -S localhost:8000 index.php
|
*/

$service = fn (string $env, string $default) => rtrim(getenv($env) ?: $default, '/');

$auth = $service('AUTH_SERVICE_URL', 'http://127.0.0.1:8001');
$org = $service('ORG_SERVICE_URL', 'http://127.0.0.1:8002');
$request = $service('REQUEST_SERVICE_URL', 'http://127.0.0.1:8003');
$file = $service('FILE_SERVICE_URL', 'http://127.0.0.1:8004');
$report = $service('REPORT_SERVICE_URL', 'http://127.0.0.1:8005');

return [
    // Module 1 - Auth Service
    '/api/v1' => $auth,
    '/api/auth' => $auth,
    '/api/users' => $auth,

    // Module 2 - Org Service (Catalog)
    '/api/departments' => $org,
    '/api/support-types' => $org,
    '/api/staff-candidates' => $org,
    '/api/faqs' => $org,
    '/api/catalog' => $org,

    // Module 3 - Request Service
    '/api/requests' => $request,
    '/api/sla' => $request,

    // Module 4 - File Service (Discussions & Documents)
    '/api/news' => $file,
    '/api/admin/news' => $file,

    // Module 5 - Report Service
    '/api/reports' => $report,
    '/api/ratings' => $report,
];
