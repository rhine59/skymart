<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/src/ListingService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function api_response(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
function api_error(string $code, string $message, int $status, array $fields = []): never {
    $error=['code'=>$code,'message'=>$message];
    if ($fields !== []) $error['fields']=$fields;
    api_response(['error'=>$error],$status);
}
function positive_int(mixed $value, int $default, int $max): int {
    if ($value === null || $value === '') return $default;
    if (filter_var($value,FILTER_VALIDATE_INT) === false || (int)$value < 1) api_error('validation_error','Invalid pagination value.',422);
    return min((int)$value,$max);
}

$method=$_SERVER['REQUEST_METHOD'];
$path=trim((string)($_SERVER['PATH_INFO'] ?? ''),'/');
$service=new ListingService($link);

if ($method === 'GET' && ($path === '' || $path === 'health')) api_response(['status'=>'ok','api'=>'v1']);
if ($method === 'GET' && $path === 'categories') api_response(['data'=>$service->categories()]);

if ($method === 'GET' && $path === 'listings') {
    $q=trim((string)($_GET['q'] ?? '')); if (mb_strlen($q) > 120) api_error('validation_error','Search text is too long.',422);
    $category=trim((string)($_GET['category'] ?? '')); if (mb_strlen($category) > 80) api_error('validation_error','Category is invalid.',422);
    api_response($service->search($q !== '' ? $q:null,$category !== '' ? $category:null,positive_int($_GET['page'] ?? null,1,1000000),positive_int($_GET['per_page'] ?? null,20,50)));
}
if ($method === 'GET' && preg_match('#^listings/(\d+)$#',$path,$m)) {
    $listing=$service->find((int)$m[1]);
    if ($listing === null) api_error('not_found','Listing not found.',404);
    api_response(['data'=>$listing]);
}
api_error('not_found','API endpoint not found.',404);
