<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__).'/src/ListingService.php';
require_login();
$service=new ListingService($link);
$userId=(int)current_user_id();
$categories=$service->categories();
$error='';$notice='';
$input=['title'=>'','description'=>'','location'=>'','map_lat'=>'','map_lon'=>'','category'=>'','price_gbp'=>'','contact_name'=>'','contact_email'=>'','contact_phone'=>'','duration_days'=>'30'];
if($_SERVER['REQUEST_METHOD']==='POST') {
 require_csrf();
 $action=(string)($_POST['action']??'');
 try {
  if($action==='relist') {
   $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);$days=(int)($_POST['duration_days']??30);
   if(!$id || !$service->relistOwned($id,$userId,$days)) throw new InvalidArgumentException('Unable to relist this advert.');
   header('Location: sell.php?published=1',true,303);exit;
  }
  if($action==='withdraw') {
   $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
   if(!$id || !$service->withdrawOwned($id,$userId)) throw new InvalidArgumentException('Unable to withdraw this advert.');
   header('Location: sell.php?withdrawn=1',true,303);exit;
  }
  if($action!=='publish' && $action!=='update') throw new InvalidArgumentException('Invalid action.');
  foreach($input as $key=>$_) $input[$key]=trim((string)($_POST[$key]??''));
  if(mb_strlen($input['title'])<3 || mb_strlen($input['title'])>160) throw new InvalidArgumentException('Title must contain 3–160 characters.');
  if(mb_strlen($input['description'])<10 || mb_strlen($input['description'])>10000) throw new InvalidArgumentException('Description must contain 10–10000 characters.');
  if($input['location']==='' || mb_strlen($input['location'])>160) throw new InvalidArgumentException('Enter a location (maximum 160 characters).');
  if(!is_numeric($input['price_gbp']) || (float)$input['price_gbp']<0 || (float)$input['price_gbp']>9999999999.99) throw new InvalidArgumentException('Enter a valid price.');
  if(!in_array((int)$input['duration_days'],[30,60,90],true)) throw new InvalidArgumentException('Choose a 30, 60 or 90-day duration.');
  if($input['contact_name']==='' || mb_strlen($input['contact_name'])>160) throw new InvalidArgumentException('Enter a contact name.');
  if(!filter_var($input['contact_email'],FILTER_VALIDATE_EMAIL) || mb_strlen($input['contact_email'])>254) throw new InvalidArgumentException('Enter a valid contact email.');
  if(mb_strlen($input['contact_phone'])>40) throw new InvalidArgumentException('Contact telephone is too long.');
  $input['duration_days']=(int)$input['duration_days'];
  $input['price_gbp']=(float)$input['price_gbp'];
  if($action==='update') {
   $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
   if(!$id || !$service->updateOwned($id,$userId,$input)) throw new InvalidArgumentException('Unable to edit this advert.');
  } else $service->create($userId,$input);
  header('Location: sell.php?published=1',true,303);exit;
 } catch(InvalidArgumentException $e) {$error=$e->getMessage();}
}
$mine=$service->mine($userId);
$editId=filter_var($_GET['edit']??null,FILTER_VALIDATE_INT);
$editing=null;
if($editId) foreach($mine as $item) if($item['id']===$editId && in_array($item['status'],['active','draft'],true)) $editing=$item;
if($editing && $_SERVER['REQUEST_METHOD']!=='POST') $input=['title'=>$editing['title'],'description'=>$editing['description'],'location'=>$editing['location'],'map_lat'=>(string)($editing['map_lat']??''),'map_lon'=>(string)($editing['map_lon']??''),'category'=>$editing['category']['slug'],'price_gbp'=>(string)$editing['price_gbp'],'contact_name'=>$editing['contact_name']??'','contact_email'=>$editing['contact_email']??'','contact_phone'=>$editing['contact_phone']??'','duration_days'=>(string)($editing['duration_days']??30)];
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sell | SkyMart</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body><main class="container py-4"><nav class="mb-4"><a href="browse.php">Buy / Browse</a> · <a href="saved.php">Saved</a> · <a href="private.php">My account</a></nav><h1>Sell on SkyMart</h1><p class="text-muted">Create an advert for aviation equipment or an aircraft. Buyers can contact you through SkyMart.</p>
<?php if($error!==''):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<?php if(isset($_GET['published'])):?><div class="alert alert-success">Advert saved successfully.</div><?php endif;?>
<?php if(isset($_GET['withdrawn'])):?><div class="alert alert-success">Advert withdrawn.</div><?php endif;?>
<h2 class="h4"><?= $editing?'Edit advert':'Create advert' ?></h2><form method="post" class="row g-3 mb-5"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="<?=$editing?'update':'publish'?>"><input type="hidden" name="id" value="<?=(int)($editing['id']??0)?>"><div class="col-md-8"><label class="form-label w-100">Title<input name="title" required minlength="3" maxlength="160" class="form-control" value="<?=e($input['title'])?>"></label></div><div class="col-md-4"><label class="form-label w-100">Price (£)<input type="number" name="price_gbp" required min="0" max="9999999999" step="0.01" class="form-control" value="<?=e((string)$input['price_gbp'])?>"></label></div><div class="col-md-6"><label class="form-label w-100">Category<select name="category" required class="form-select"><option value="">Choose category</option><?php foreach($categories as $category):?><option value="<?=e($category['slug'])?>" <?=$input['category']===$category['slug']?'selected':''?>><?=e($category['name'])?></option><?php endforeach;?></select></label></div><div class="col-md-6"><label class="form-label w-100">Location<input name="location" required maxlength="160" class="form-control" value="<?=e($input['location'])?>"></label></div><div class="col-12"><h3 class="h6">Map location (optional)</h3><p class="text-muted small">Enter an approximate location or meeting point. Both coordinates are required to show a map. Avoid publishing your home address.</p></div><div class="col-md-6"><label class="form-label w-100">Latitude<input name="map_lat" type="number" step="any" min="-90" max="90" class="form-control" value="<?=e($input['map_lat'])?>" placeholder="e.g. 53.9621"></label></div><div class="col-md-6"><label class="form-label w-100">Longitude<input name="map_lon" type="number" step="any" min="-180" max="180" class="form-control" value="<?=e($input['map_lon'])?>" placeholder="e.g. -2.0167"></label></div><div class="col-12"><label class="form-label w-100">Description<textarea name="description" required minlength="10" maxlength="10000" rows="5" class="form-control"><?=e($input['description'])?></textarea></label></div><div class="col-md-4"><label class="form-label w-100">Contact name<input name="contact_name" required maxlength="160" class="form-control" value="<?=e($input['contact_name'])?>"></label></div><div class="col-md-4"><label class="form-label w-100">Contact email<input type="email" name="contact_email" required maxlength="254" class="form-control" value="<?=e($input['contact_email'])?>"></label></div><div class="col-md-4"><label class="form-label w-100">Contact telephone (optional)<input name="contact_phone" maxlength="40" class="form-control" value="<?=e($input['contact_phone'])?>"></label></div><div class="col-md-6"><label class="form-label w-100">Listing duration<select name="duration_days" class="form-select"><?php foreach([30,60,90] as $days):?><option value="<?=$days?>" <?=(int)$input['duration_days']===$days?'selected':''?>><?=$days?> days — Free</option><?php endforeach;?></select></label></div><div class="col-12"><p class="text-muted">No listing fee during the introductory free period. Payment checkout will be introduced later.</p></div><div class="col-12"><button class="btn btn-primary" type="submit"><?=$editing?'Save changes':'Publish advert'?></button><?php if($editing):?><a class="btn btn-outline-secondary ms-2" href="sell.php">Cancel</a><?php endif;?></div></form>
<h2 class="h4">My adverts</h2><?php if(!$mine):?><p>You have not published any adverts.</p><?php endif;?>
<?php foreach($mine as $item):?><div class="border-bottom py-3 d-flex flex-wrap gap-3 align-items-center"><div class="me-auto"><strong><?=e($item['title'])?></strong><div class="small text-muted">£<?=e(number_format((float)$item['price_gbp'],2))?> · <?=e($item['status'])?></div></div><?php if($item['status']==='active'):?><a class="btn btn-sm btn-outline-primary" href="browse.php?listing=<?=(int)$item['id']?>">View</a><a class="btn btn-sm btn-outline-secondary" href="sell.php?edit=<?=(int)$item['id']?>">Edit</a><a class="btn btn-sm btn-outline-secondary" href="photos.php?id=<?=(int)$item['id']?>">Photos</a><form method="post" onsubmit="return confirm('Withdraw this advert?')"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="withdraw"><input type="hidden" name="id" value="<?=(int)$item['id']?>"><button class="btn btn-sm btn-outline-danger">Withdraw</button></form><?php endif;?><?php if(in_array($item['status'],['expired','withdrawn'],true) || ($item['status']==='active' && !empty($item['expires_at']) && strtotime($item['expires_at'])<=time())):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="relist"><input type="hidden" name="id" value="<?=(int)$item['id']?>"><select name="duration_days" aria-label="Relist duration"><?php foreach([30,60,90] as $days):?><option value="<?=$days?>"><?=$days?> days</option><?php endforeach;?></select><button class="btn btn-sm btn-success">Relist free</button></form><?php endif;?></div><?php endforeach;?></main></body></html>
