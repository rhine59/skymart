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
function json_body(): array {
    $raw=file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    try { $data=json_decode($raw,true,512,JSON_THROW_ON_ERROR); }
    catch (JsonException) { api_error('invalid_json','Request body must be valid JSON.',400); }
    if (!is_array($data)) api_error('invalid_json','Request body must be a JSON object.',400);
    return $data;
}
function api_user_id(): int {
    $id=current_user_id();
    if ($id === null) api_error('unauthorized','Authentication required.',401);
    return $id;
}
function listing_input(array $body): array {
    $title=trim((string)($body['title'] ?? ''));$description=trim((string)($body['description'] ?? ''));
    $location=trim((string)($body['location'] ?? ''));$category=trim((string)($body['category'] ?? ''));
    $price=$body['price_gbp'] ?? null;$fields=[];
    if (mb_strlen($title)<3 || mb_strlen($title)>160) $fields['title']='Use 3-160 characters.';
    if (mb_strlen($description)<10 || mb_strlen($description)>10000) $fields['description']='Use 10-10000 characters.';
    if ($location==='' || mb_strlen($location)>160) $fields['location']='Location is required (maximum 160 characters).';
    if ($category==='' || mb_strlen($category)>80) $fields['category']='Category is required.';
    if (!is_numeric($price) || (float)$price<0 || (float)$price>9999999999.99) $fields['price_gbp']='Enter a valid non-negative price.';
    if ($fields !== []) api_error('validation_error','The request could not be validated.',422,$fields);
    return ['title'=>$title,'description'=>$description,'location'=>$location,'category'=>$category,'price_gbp'=>(float)$price];
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
if ($method === 'GET' && $path === 'me/listings') api_response(['data'=>$service->mine(api_user_id())]);

if ($method === 'POST' && $path === 'listings') {
    try { $listing=$service->create(api_user_id(),listing_input(json_body())); }
    catch (InvalidArgumentException) { api_error('validation_error','The request could not be validated.',422,['category'=>'Unknown category.']); }
    api_response(['data'=>$listing],201);
}
if ($method === 'PATCH' && preg_match('#^listings/(\\d+)$#',$path,$m)) {
    try { $listing=$service->updateOwned((int)$m[1],api_user_id(),listing_input(json_body())); }
    catch (InvalidArgumentException) { api_error('validation_error','The request could not be validated.',422,['category'=>'Unknown category.']); }
    if ($listing === null) api_error('not_found','Listing not found.',404);
    api_response(['data'=>$listing]);
}
if ($method === 'DELETE' && preg_match('#^listings/(\\d+)$#',$path,$m)) {
    if (!$service->withdrawOwned((int)$m[1],api_user_id())) api_error('not_found','Listing not found.',404);
    api_response(['data'=>['id'=>(int)$m[1],'status'=>'withdrawn']]);
}
api_error('not_found','API endpoint not found.',404);
