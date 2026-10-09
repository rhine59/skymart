<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__).'/src/ListingService.php';
require_once dirname(__DIR__).'/src/ImageService.php';
require_login();
$userId=(int)current_user_id();$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
$service=new ListingService($link);$listing=null;
if($id)foreach($service->mine($userId) as $item)if($item['id']===$id)$listing=$item;
if(!$listing || !in_array($listing['status'],['draft','active'],true)){http_response_code(404);exit('Advert not found.');}
$images=new ImageService($link,(string)(getenv('SKYMART_UPLOAD_ROOT')?:'/var/lib/skymart/uploads'));
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 require_csrf();
 try{
  $action=(string)($_POST['action']??'');
  if($action==='upload'){
   if(empty($_FILES['photos']['name']) || !is_array($_FILES['photos']['name']))throw new InvalidArgumentException('Choose one or more photographs.');
   foreach($_FILES['photos']['name'] as $i=>$name){
    if(($_FILES['photos']['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Photo upload failed. Check the file size.');
    if(($_FILES['photos']['size'][$i]??0)>12582912)throw new InvalidArgumentException('Maximum 12 MB per photo.');
    $path=$_FILES['photos']['tmp_name'][$i]??'';
    if(!is_uploaded_file($path))throw new InvalidArgumentException('Invalid upload.');
    $bytes=file_get_contents($path);
    if($bytes===false)throw new InvalidArgumentException('Unable to read photo.');
    $images->addJpeg($id,$userId,$bytes);
   }
  }elseif($action==='order_all'){
   $raw=(string)($_POST['ordered_ids']??'');
   if(!preg_match('/^[0-9]+(?:,[0-9]+)*$/',$raw))throw new InvalidArgumentException('Invalid photo order.');
   $images->reorder($id,$userId,array_map('intval',explode(',',$raw)));
  }elseif($action==='delete'){
   $imageId=filter_var($_POST['image_id']??null,FILTER_VALIDATE_INT);
   if(!$imageId || !$images->delete($imageId,$id,$userId))throw new InvalidArgumentException('Photo not found.');
  }elseif($action==='reorder'){
   $ids=array_map('intval',array_column($listing['images'],'id'));
   $imageId=filter_var($_POST['image_id']??null,FILTER_VALIDATE_INT);
   $pos=array_search($imageId,$ids,true);
   $direction=(string)($_POST['direction']??'');
   if($pos===false || !in_array($direction,['up','down'],true))throw new InvalidArgumentException('Invalid photo order.');
   $target=$pos+($direction==='up'?-1:1);
   if($target>=0 && $target<count($ids)){
    [$ids[$pos],$ids[$target]]=[$ids[$target],$ids[$pos]];
    $images->reorder($id,$userId,$ids);
   }
  }else throw new InvalidArgumentException('Unknown action.');
  header('Location: photos.php?id='.$id,true,303);exit;
 }catch(InvalidArgumentException|RuntimeException $e){$error=$e->getMessage();}
 $listing=null;foreach($service->mine($userId) as $item)if($item['id']===$id)$listing=$item;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Advert photos | SkyMart</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body><main class="container py-4"><nav><a href="sell.php">My adverts</a> · <a href="browse.php">Buy</a></nav><h1 class="mt-3">Photos for <?=e($listing['title'])?></h1><p>Up to 10 photographs, maximum 12 MB each. JPEG, PNG and other supported image files are converted to JPEG. The first photo is the cover image.</p><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><form method="post" enctype="multipart/form-data" class="mb-4"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="upload"><label class="form-label">Add photos<input class="form-control" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required></label><button class="btn btn-primary ms-2">Upload photos</button></form><p class="text-muted">Drag photos to rearrange them, then save. The first photo becomes the cover image.</p><form id="photo-order" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="order_all"><input type="hidden" name="ordered_ids" id="ordered-ids"><button type="submit" class="btn btn-success mb-3" id="save-order" disabled>Save photo order</button></form><div class="row g-3" id="photo-grid"><?php foreach($listing['images'] as $i=>$photo):?><div class="col-sm-6 col-md-4 col-lg-3" draggable="true" data-photo-id="<?=(int)$photo['id']?>"><div class="card h-100"><img class="card-img-top" style="height:180px;object-fit:cover" src="<?=e($photo['thumbnail_url'])?>" alt="Advert photo"><div class="card-body"><p class="small mb-2"><?=$i===0?'Cover photo':'Photo '.($i+1)?></p><div class="d-flex gap-1"><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="reorder"><input type="hidden" name="image_id" value="<?=(int)$photo['id']?>"><input type="hidden" name="direction" value="up"><button class="btn btn-sm btn-outline-secondary" <?=$i===0?'disabled':''?>>←</button></form><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="reorder"><input type="hidden" name="image_id" value="<?=(int)$photo['id']?>"><input type="hidden" name="direction" value="down"><button class="btn btn-sm btn-outline-secondary" <?=$i===count($listing['images'])-1?'disabled':''?>>→</button></form><form method="post" onsubmit="return confirm('Delete this photo?')"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="image_id" value="<?=(int)$photo['id']?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></div></div></div></div><?php endforeach;?></div><p class="mt-4"><a class="btn btn-outline-primary" href="sell.php?edit=<?=$id?>">Back to advert details</a></p><script>const grid=document.getElementById('photo-grid'),save=document.getElementById('save-order'),ordered=document.getElementById('ordered-ids');let dragging=null;grid.addEventListener('dragstart',e=>{dragging=e.target.closest('[data-photo-id]');if(dragging)e.dataTransfer.effectAllowed='move'});grid.addEventListener('dragover',e=>{const target=e.target.closest('[data-photo-id]');if(!dragging||!target||target===dragging)return;e.preventDefault();const r=target.getBoundingClientRect();grid.insertBefore(dragging,e.clientX<r.left+r.width/2?target:target.nextSibling);save.disabled=false});grid.addEventListener('dragend',()=>{dragging=null});document.getElementById('photo-order').addEventListener('submit',()=>{ordered.value=[...grid.querySelectorAll('[data-photo-id]')].map(el=>el.dataset.photoId).join(',')});</script></main></body></html>
