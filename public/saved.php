<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__).'/src/ListingService.php';
require_once dirname(__DIR__).'/src/PersonalMarketplaceService.php';
require_login();
$userId=(int)current_user_id();
$personal=new PersonalMarketplaceService($link,new ListingService($link));
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 if(!csrf_verify((string)($_POST['csrf_token']??''))) {http_response_code(403);exit('Invalid CSRF token');}
 try {
  $action=(string)($_POST['action']??'');$id=(int)($_POST['id']??0);
  if($action==='favourite' && $id>0) $personal->favourite($userId,$id);
  elseif($action==='unfavourite' && $id>0) $personal->unfavourite($userId,$id);
  elseif($action==='delete' && $id>0) $personal->deleteSearch($userId,$id);
  elseif($action==='save') {
   $personal->saveSearch($userId,$id>0?$id:null,['name'=>$_POST['name']??'','query_text'=>$_POST['query_text']??'','category_slug'=>$_POST['category_slug']??'','frequency'=>$_POST['frequency']??'daily','enabled'=>isset($_POST['enabled'])]);
  } else throw new InvalidArgumentException('Invalid action.');
  header('Location: saved.php',true,303);exit;
 } catch(InvalidArgumentException $e) {$error=$e->getMessage();}
}
$editId=(int)($_GET['edit']??0);$edit=$editId>0?$personal->getSearch($userId,$editId):null;
$favourites=$personal->favourites($userId);$searches=$personal->searches($userId);
$categories=(new ListingService($link))->categories();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Saved | SkyMart</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body><main class="container py-4"><nav class="mb-4"><a href="browse.php">Browse adverts</a> · <a href="private.php">My account</a></nav><h1>My saved items</h1>
<?php if($error!==''):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<h2 class="h4 mt-4">Favourites</h2><?php if(!$favourites):?><p>No favourite adverts yet.</p><?php endif;?>
<?php foreach($favourites as $item):?><div class="d-flex gap-3 align-items-center border-bottom py-2"><a href="browse.php?listing=<?=(int)$item['id']?>"><?=e($item['title'])?></a><span>£<?=e(number_format((float)$item['price_gbp'],2))?></span><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="unfavourite"><input type="hidden" name="id" value="<?=(int)$item['id']?>"><button class="btn btn-sm btn-outline-danger">Remove</button></form></div><?php endforeach;?>
<h2 class="h4 mt-5">Saved searches</h2><p class="text-muted">Notifications by email, text and WhatsApp will become available after contact verification and consent are configured.</p>
<?php foreach($searches as $search):?><div class="d-flex gap-3 align-items-center border-bottom py-2"><strong><?=e($search['name'])?></strong><span><?=e($search['query_text'])?> · <?=e($search['category_slug']?:'All categories')?></span><a href="browse.php?<?=e(http_build_query(['q'=>$search['query_text'],'category'=>$search['category_slug']]))?>">Run</a><a href="saved.php?edit=<?=(int)$search['id']?>">Edit</a><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$search['id']?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></div><?php endforeach;?>
<h3 class="h5 mt-4"><?= $edit?'Edit search':'New saved search' ?></h3><form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=(int)($edit['id']??0)?>"><div class="col-md-4"><label class="form-label">Name<input required maxlength="120" name="name" class="form-control" value="<?=e($edit['name']??'')?>"></label></div><div class="col-md-4"><label class="form-label">Keywords<input maxlength="120" name="query_text" class="form-control" value="<?=e($edit['query_text']??'')?>"></label></div><div class="col-md-4"><label class="form-label">Category<select name="category_slug" class="form-select"><option value="">All categories</option><?php foreach($categories as $category):?><option value="<?=e($category['slug'])?>" <?=($edit['category_slug']??'')===$category['slug']?'selected':''?>><?=e($category['name'])?></option><?php endforeach;?></select></label></div><div class="col-md-3"><label class="form-label">Frequency<select name="frequency" class="form-select"><?php foreach(['immediate','daily','weekly'] as $frequency):?><option value="<?=$frequency?>" <?=($edit['frequency']??'daily')===$frequency?'selected':''?>><?=ucfirst($frequency)?></option><?php endforeach;?></select></label></div><div class="col-md-3 d-flex align-items-end"><label><input type="checkbox" name="enabled" <?=!$edit||$edit['enabled']?'checked':''?>> Enabled</label></div><div class="col-12"><button class="btn btn-primary">Save search</button></div></form></main></body></html>
