<?php
declare(strict_types=1);
$directHttps=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
$trustedProxy=getenv('SKYMART_TRUST_PROXY')==='1';
$forwardedHttps=$trustedProxy&&strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO']??''))==='https';
$isHttps=$directHttps||$forwardedHttps;
ini_set('session.use_only_cookies','1');ini_set('session.use_strict_mode','1');ini_set('session.use_trans_sid','0');
session_name('skymart_session');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$isHttps,'httponly'=>true,'samesite'=>'Lax']);
session_start();
require_once __DIR__.'/database.php';require_once __DIR__.'/lib/security.php';
