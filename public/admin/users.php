<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/bootstrap.php';
require_once dirname(__DIR__,2).'/src/AccountService.php';
require_once dirname(__DIR__,2).'/src/AdminAccountService.php';
require_login();
$accounts=new AccountService($link);$admin=new AdminAccountService($link);$me=$accounts->profile((int)current_user_id());
if(!$me||$me['role']!=='admin'||$me['status']!=='active'){http_response_code(403);exit('Administrator access required.');}
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){require_csrf();$target=(int)($_POST['user_id']??0);$action=(string)($_POST['action']??'');
 if($action==='disable'&&$target===(int)$me['id'])$message='You cannot disable your own administrator account.';
 elseif(in_array($action,['disable','enable'],true)){$admin->setStatus($target,$action==='disable'?'disabled':'active');$message='Account status updated.';}
 elseif($action==='revoke'){$admin->revokeDevices($target);$message='Device sessions revoked.';}}
$q=mb_substr(trim((string)($_GET['q']??'')),0,120);$users=$admin->list($q);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SkyMart Admin - Users</title></head><body><main><h1>SkyMart Admin — Users</h1><p>Signed in as <?=e($me['name'])?> · <a href="../private.php">SkyMart</a></p><?php if($message):?><p><?=e($message)?></p><?php endif;?><form method="get"><label>Search <input name="q" value="<?=e($q)?>"></label><button>Search</button></form><table><thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach($users as $u):?><tr><td><?=e($u['name'])?></td><td><?=e($u['email'])?></td><td><?=e($u['role'])?></td><td><?=e($u['status'])?></td><td><?php if($u['status']==='active'):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="user_id" value="<?=(int)$u['id']?>"><input type="hidden" name="action" value="disable"><button <?=((int)$u['id']===(int)$me['id'])?'disabled':''?>>Disable</button></form><?php elseif($u['status']==='disabled'):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="user_id" value="<?=(int)$u['id']?>"><input type="hidden" name="action" value="enable"><button>Enable</button></form><?php endif;?><form method="post" onsubmit="return confirm('Revoke all mobile sessions for this account?')"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="user_id" value="<?=(int)$u['id']?>"><input type="hidden" name="action" value="revoke"><button>Revoke devices</button></form></td></tr><?php endforeach;?></tbody></table></main></body></html>