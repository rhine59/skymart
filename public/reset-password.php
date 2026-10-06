<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__).'/src/AccountService.php';
$accounts=new AccountService($link);$token=strtolower(trim((string)($_GET['token']??$_POST['token']??'')));$message='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){require_csrf();$password=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm']??'');
 if(strlen($password)<12)$error='Use at least 12 characters.';elseif($password!==$confirm)$error='Passwords do not match.';elseif(!$accounts->resetPassword($token,$password))$error='This reset link is invalid or has expired.';else $message='Password reset. You can now sign in again.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset SkyMart password</title></head><body><main><h1>Reset password</h1><?php if($message):?><p><?=e($message)?></p><p><a href="index.php">Sign in</a></p><?php else:?><p><?=e($error)?></p><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="token" value="<?=e($token)?>"><label>New password <input type="password" name="password" minlength="12" required></label><label>Repeat password <input type="password" name="confirm" minlength="12" required></label><button type="submit">Reset password</button></form><?php endif;?></main></body></html>
