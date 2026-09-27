<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function api_response(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error(string $code, string $message, int $status): never {
    api_response(['error' => ['code' => $code, 'message' => $message]], $status);
}

$path = trim((string)($_SERVER['PATH_INFO'] ?? ''), '/');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($path === '' || $path === 'health')) {
    api_response(['status' => 'ok', 'api' => 'v1']);
}

api_error('not_found', 'API endpoint not found.', 404);
