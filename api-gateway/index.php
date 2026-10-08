<?php

/*
|--------------------------------------------------------------------------
| API GATEWAY (http://localhost:8000)
|--------------------------------------------------------------------------
|
| Frontend chỉ gọi một địa chỉ duy nhất là Gateway. Gateway tra bảng
| routes.php để biết request thuộc service nào rồi chuyển tiếp sang đó.
| Chạy: php -S localhost:8000 index.php
|
*/

// Header được chuyển tiếp từ frontend xuống service.
const FORWARDED_HEADERS = ['Accept', 'Content-Type', 'Authorization', 'X-User-Id', 'X-User-Role'];

$routes = require __DIR__.'/routes.php';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

sendCorsHeaders();

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($path === '/' || $path === '/health') {
    respondJson(200, ['success' => true, 'data' => ['routes' => $routes], 'message' => 'API Gateway đang chạy.']);
}

$target = findService($routes, $path);

if ($target === null) {
    respondJson(404, ['success' => false, 'message' => 'Không có service nào xử lý đường dẫn này.', 'errors' => null]);
}

forward($target.$_SERVER['REQUEST_URI'], $method);

/**
 * Chọn service có tiền tố dài nhất khớp với đường dẫn.
 */
function findService(array $routes, string $path): ?string
{
    $matched = null;
    $matchedLength = 0;

    foreach ($routes as $prefix => $serviceUrl) {
        $isMatch = $path === $prefix || str_starts_with($path, $prefix.'/');

        if ($isMatch && strlen($prefix) > $matchedLength) {
            $matched = $serviceUrl;
            $matchedLength = strlen($prefix);
        }
    }

    return $matched;
}

/**
 * Gửi request sang service và trả nguyên response về cho frontend.
 */
function forward(string $url, string $method): void
{
    $headers = [];
    foreach (FORWARDED_HEADERS as $name) {
        $value = $_SERVER['HTTP_'.strtoupper(str_replace('-', '_', $name))]
            ?? ($name === 'Content-Type' ? ($_SERVER['CONTENT_TYPE'] ?? null) : null);

        if ($value !== null && $value !== '') {
            $headers[$name] = $value;
        }
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 60,
    ]);

    $isMultipart = str_starts_with($headers['Content-Type'] ?? '', 'multipart/form-data');

    if ($isMultipart) {
        // PHP đã tách sẵn multipart vào $_POST/$_FILES; curl tự tạo lại boundary mới.
        unset($headers['Content-Type']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, multipartFields());
    } elseif (! in_array($method, ['GET', 'HEAD'], true)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, array_map(
        fn ($name, $value) => "{$name}: {$value}",
        array_keys($headers),
        $headers
    ));

    $response = curl_exec($curl);

    if ($response === false) {
        respondJson(502, [
            'success' => false,
            'message' => 'Không kết nối được tới service: '.parse_url($url, PHP_URL_HOST).':'.parse_url($url, PHP_URL_PORT).'.',
            'errors' => null,
        ]);
    }

    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

    http_response_code($status);
    foreach (explode("\r\n", substr($response, 0, $headerSize)) as $line) {
        if (preg_match('/^(Content-Type|Content-Disposition):/i', $line)) {
            header($line);
        }
    }

    echo substr($response, $headerSize);
    exit;
}

/**
 * Dựng lại dữ liệu multipart (form + file đính kèm) để gửi tiếp.
 */
function multipartFields(): array
{
    $fields = [];

    foreach ($_POST as $key => $value) {
        flatten($fields, $key, $value);
    }

    foreach ($_FILES as $key => $file) {
        if (is_array($file['tmp_name'])) {
            foreach ($file['tmp_name'] as $index => $tmpName) {
                if ($file['error'][$index] === UPLOAD_ERR_OK) {
                    $fields["{$key}[{$index}]"] = new CURLFile($tmpName, $file['type'][$index], $file['name'][$index]);
                }
            }
        } elseif ($file['error'] === UPLOAD_ERR_OK) {
            $fields[$key] = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
        }
    }

    return $fields;
}

/**
 * Trải phẳng mảng lồng nhau thành dạng key[a][b] mà multipart yêu cầu.
 */
function flatten(array &$fields, string $key, mixed $value): void
{
    if (! is_array($value)) {
        $fields[$key] = $value;

        return;
    }

    foreach ($value as $childKey => $childValue) {
        flatten($fields, "{$key}[{$childKey}]", $childValue);
    }
}

function sendCorsHeaders(): void
{
    header('Access-Control-Allow-Origin: '.($_SERVER['HTTP_ORIGIN'] ?? '*'));
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: '.implode(', ', FORWARDED_HEADERS));
    header('Access-Control-Expose-Headers: Content-Disposition');
    header('Vary: Origin');
}

function respondJson(int $status, array $body): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
