<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/src/ListingService.php';
require_once dirname(__DIR__, 3) . '/src/PersonalMarketplaceService.php';
require_once dirname(__DIR__, 3) . '/src/AuthService.php';
require_once dirname(__DIR__, 3) . '/src/AccountService.php';
require_once dirname(__DIR__, 3) . '/src/AdminAccountService.php';
require_once dirname(__DIR__, 3) . '/src/MailService.php';
require_once dirname(__DIR__, 3) . '/src/ImageService.php';

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
function bearer_token(): ?string {
    $header=$_SERVER['HTTP_AUTHORIZATION'] ?? '';
    return preg_match('/^Bearer ([A-Fa-f0-9]{64})$/',$header,$m) ? strtolower($m[1]) : null;
}
function api_identity(AuthService $auth): array {
    if (($token=bearer_token()) !== null && ($identity=$auth->authenticate($token)) !== null) return $identity;
    $id=current_user_id();
    if ($id !== null) return ['id'=>$id,'token_id'=>null];
    api_error('unauthorized','Authentication required.',401);
}
function api_user_id(AuthService $auth): int { return (int)api_identity($auth)['id']; }
function api_admin_id(AuthService $auth,AccountService $accounts): int {
    $id=api_user_id($auth);$profile=$accounts->profile($id);
    if(!$profile || $profile['role']!=='admin' || $profile['status']!=='active') api_error('forbidden','Administrator access required.',403);
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
$personal=new PersonalMarketplaceService($link,$service);
$auth=new AuthService($link);
$accounts=new AccountService($link);
$adminAccounts=new AdminAccountService($link);
$mail=new MailService();
$images=new ImageService($link,(string)(getenv('SKYMART_UPLOAD_ROOT') ?: '/var/lib/skymart/uploads'));

if ($method === 'GET' && ($path === '' || $path === 'health')) api_response(['status'=>'ok','api'=>'v1']);
if ($method === 'GET' && $path === 'categories') api_response(['data'=>$service->categories()]);

if ($method === 'POST' && $path === 'auth/login') {
    $body=json_body();$email=mb_strtolower(trim((string)($body['email'] ?? '')));$password=(string)($body['password'] ?? '');
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || $password==='') api_error('validation_error','Email and password are required.',422);
    $result=$auth->login($email,$password,mb_substr(trim((string)($body['device_name'] ?? 'iPhone')),0,80) ?: 'iPhone');
    if ($result===null) api_error('invalid_credentials','Invalid email or password.',401);
    api_response(['data'=>$result]);
}
if ($method === 'GET' && $path === 'me') {
    $identity=api_identity($auth);unset($identity['token_id']);api_response(['data'=>$identity]);
}
if ($method === 'POST' && $path === 'auth/logout') {
    $identity=api_identity($auth);if ($identity['token_id'] !== null) $auth->revoke((int)$identity['token_id']);
    api_response(['data'=>['logged_out'=>true]]);
}

if ($method === 'PATCH' && $path === 'me') {
    $id=api_user_id($auth);$body=json_body();$name=trim((string)($body['name']??''));$phone=trim((string)($body['phone']??''));
    $fields=[];if($name===''||mb_strlen($name)>120)$fields['name']='Name is required (maximum 120 characters).';if(mb_strlen($phone)>40)$fields['phone']='Maximum 40 characters.';
    if($fields)api_error('validation_error','The request could not be validated.',422,$fields);
    api_response(['data'=>$accounts->updateProfile($id,$name,$phone)]);
}
if ($method === 'POST' && $path === 'me/password') {
    $body=json_body();$new=(string)($body['new_password']??'');if(strlen($new)<12)api_error('validation_error','New password must be at least 12 characters.',422);
    if(!$accounts->changePassword(api_user_id($auth),(string)($body['current_password']??''),$new))api_error('invalid_credentials','Current password is incorrect.',401);
    api_response(['data'=>['password_changed'=>true,'reauthentication_required'=>true]]);
}
if ($method === 'POST' && $path === 'auth/password-reset/request') {
    $body=json_body();$email=mb_strtolower(trim((string)($body['email']??'')));
    if(filter_var($email,FILTER_VALIDATE_EMAIL)){ $reset=$accounts->issueReset($email); if($reset!==null)$mail->sendPasswordReset($email,$reset); }
    // Always identical response to prevent account enumeration. Delivery is added with the mail service.
    api_response(['data'=>['accepted'=>true]]);
}
if ($method === 'POST' && $path === 'auth/password-reset/confirm') {
    $body=json_body();$new=(string)($body['new_password']??'');if(strlen($new)<12)api_error('validation_error','New password must be at least 12 characters.',422);
    if(!$accounts->resetPassword(strtolower((string)($body['token']??'')),$new))api_error('invalid_or_expired_token','Reset token is invalid or expired.',400);
    api_response(['data'=>['password_reset'=>true]]);
}
if ($method === 'DELETE' && $path === 'me') {
    $body=json_body();if(!$accounts->deactivate(api_user_id($auth),(string)($body['password']??'')))api_error('invalid_credentials','Password is incorrect.',401);
    $_SESSION=[];if(session_status()===PHP_SESSION_ACTIVE)session_destroy();
    api_response(['data'=>['deactivated'=>true]]);
}

if ($method === 'GET' && $path === 'admin/users') {
    api_admin_id($auth,$accounts);$q=mb_substr(trim((string)($_GET['q']??'')),0,120);api_response(['data'=>$adminAccounts->list($q)]);
}
if ($method === 'PATCH' && preg_match('#^admin/users/(\\d+)/status$#',$path,$m)) {
    $adminId=api_admin_id($auth,$accounts);$target=(int)$m[1];$status=(string)(json_body()['status']??'');
    if($target===$adminId && $status==='disabled')api_error('validation_error','You cannot disable your own administrator account.',422);
    if(!$adminAccounts->setStatus($target,$status))api_error('not_found','Account not found or status unchanged.',404);
    api_response(['data'=>['id'=>$target,'status'=>$status]]);
}
if ($method === 'POST' && preg_match('#^admin/users/(\\d+)/revoke-devices$#',$path,$m)) {
    api_admin_id($auth,$accounts);$adminAccounts->revokeDevices((int)$m[1]);api_response(['data'=>['revoked'=>true]]);
}

if ($method === 'GET' && $path === 'me/favourites') api_response(['data'=>$personal->favourites(api_user_id($auth))]);
if (preg_match('#^me/favourites/(\\d+)$#',$path,$m)) {
    $userId=api_user_id($auth);$listingId=(int)$m[1];
    if ($method==='PUT') {
        if(!$personal->favourite($userId,$listingId)) api_error('not_found','Advert not found.',404);
        api_response(['data'=>['listing_id'=>$listingId,'favourited'=>true]]);
    }
    if ($method==='DELETE') {
        $personal->unfavourite($userId,$listingId);
        api_response(['data'=>['listing_id'=>$listingId,'favourited'=>false]]);
    }
}
if ($method==='GET' && $path==='me/saved-searches') api_response(['data'=>$personal->searches(api_user_id($auth))]);
if ($method==='POST' && $path==='me/saved-searches') {
    try { $saved=$personal->saveSearch(api_user_id($auth),null,json_body()); }
    catch(InvalidArgumentException $e) { api_error('validation_error',$e->getMessage(),422); }
    api_response(['data'=>$saved],201);
}
if (preg_match('#^me/saved-searches/(\\d+)(/results)?$#',$path,$m)) {
    $userId=api_user_id($auth);$id=(int)$m[1];$suffix=$m[2]??'';
    if($method==='GET' && $suffix==='/results') {
        $result=$personal->results($userId,$id);
        if($result===null) api_error('not_found','Saved search not found.',404);
        api_response($result);
    }
    if($suffix==='') {
        if($method==='GET') {
            $saved=$personal->getSearch($userId,$id);
            if($saved===null) api_error('not_found','Saved search not found.',404);
            api_response(['data'=>$saved]);
        }
        if($method==='PATCH') {
            try { $saved=$personal->saveSearch($userId,$id,json_body()); }
            catch(InvalidArgumentException $e) { api_error('validation_error',$e->getMessage(),422); }
            if($saved===null) api_error('not_found','Saved search not found.',404);
            api_response(['data'=>$saved]);
        }
        if($method==='DELETE') {
            $personal->deleteSearch($userId,$id);
            api_response(['data'=>['deleted'=>true]]);
        }
    }
}

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
if ($method === 'GET' && $path === 'me/listings') api_response(['data'=>$service->mine(api_user_id($auth))]);

if ($method === 'POST' && $path === 'listings') {
    try { $listing=$service->create(api_user_id($auth),listing_input(json_body())); }
    catch (InvalidArgumentException) { api_error('validation_error','The request could not be validated.',422,['category'=>'Unknown category.']); }
    api_response(['data'=>$listing],201);
}
if ($method === 'PATCH' && preg_match('#^listings/(\\d+)$#',$path,$m)) {
    try { $listing=$service->updateOwned((int)$m[1],api_user_id($auth),listing_input(json_body())); }
    catch (InvalidArgumentException) { api_error('validation_error','The request could not be validated.',422,['category'=>'Unknown category.']); }
    if ($listing === null) api_error('not_found','Listing not found.',404);
    api_response(['data'=>$listing]);
}
if ($method === 'POST' && preg_match('#^listings/(\\d+)/images$#',$path,$m)) {
    $type=strtolower(trim(explode(';',(string)($_SERVER['CONTENT_TYPE']??''))[0]));if($type!=='image/jpeg')api_error('unsupported_media_type','Upload a JPEG image.',415);
    $raw=file_get_contents('php://input');if($raw===false)api_error('invalid_image','Unable to read image.',400);
    try{$image=$images->addJpeg((int)$m[1],api_user_id($auth),$raw);}catch(InvalidArgumentException $e){api_error('validation_error',$e->getMessage(),422);}catch(RuntimeException $e){if($e->getMessage()==='not_found')api_error('not_found','Listing not found.',404);throw $e;}
    api_response(['data'=>$image],201);
}
if ($method === 'DELETE' && preg_match('#^listings/(\\d+)/images/(\\d+)$#',$path,$m)) {
    if(!$images->delete((int)$m[2],(int)$m[1],api_user_id($auth)))api_error('not_found','Image not found.',404);api_response(['data'=>['deleted'=>true]]);
}
if ($method === 'DELETE' && preg_match('#^listings/(\\d+)$#',$path,$m)) {
    if (!$service->withdrawOwned((int)$m[1],api_user_id($auth))) api_error('not_found','Listing not found.',404);
    api_response(['data'=>['id'=>(int)$m[1],'status'=>'withdrawn']]);
}
api_error('not_found','API endpoint not found.',404);
