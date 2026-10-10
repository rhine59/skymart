<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/bootstrap.php';
if(current_user_id()===null){$_SESSION['after_auth']='search-assistant.php';header('Location: login.php',true,303);exit;}
require_once dirname(__DIR__).'/src/ListingService.php';require_once dirname(__DIR__).'/src/PersonalMarketplaceService.php';require_once dirname(__DIR__).'/src/NaturalSearch.php';
$service=new ListingService($link);$personal=new PersonalMarketplaceService($link,$service);if(isset($_GET['load'])){$saved=$personal->getSearch((int)current_user_id(),(int)$_GET['load']);if($saved && !empty($saved['criteria_json'])){$_SESSION['natural_search']=json_decode($saved['criteria_json'],true)??['terms'=>[]];$_SESSION['natural_history']=[];}}$filters=$_SESSION['natural_search']??['terms'=>[]];$history=$_SESSION['natural_history']??[];$error='';$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 require_csrf();try{
 $action=(string)($_POST['action']??'');
 if($action==='reset'){$filters=['terms'=>[]];$history=[];}
 elseif($action==='refine'){$instruction=trim((string)($_POST['instruction']??''));$filters=NaturalSearch::refine($filters,$instruction,$service->categories());$history[]=$instruction;$history=array_slice($history,-12);}
 elseif($action==='save'){$personal->saveAdvancedSearch((int)current_user_id(),trim((string)($_POST['name']??'')),$filters);$notice='Search saved to your account.';}
 else throw new InvalidArgumentException('Unknown action.');
 $_SESSION['natural_search']=$filters;$_SESSION['natural_history']=$history;
 }catch(InvalidArgumentException $e){$error=$e->getMessage();}
}
$results=$service->searchAdvanced($filters,1,50);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Search assistant | SkyMart</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body><main class="container py-4"><nav><a href="browse.php">Browse</a> · <a href="saved.php">Saved searches</a></nav><h1 class="mt-4">Search assistant</h1><p>Describe what you want, then narrow your results with another instruction. Supports categories, price ranges and matching words or phrases.</p>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><?php if($notice):?><div class="alert alert-success"><?=e($notice)?></div><?php endif;?>
<form method="post" class="d-flex gap-2 mb-3"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="refine"><input name="instruction" class="form-control" maxlength="250" required aria-label="Natural language search" placeholder="e.g. Find flexwing under £15000, then ones mentioning Rotax"><button class="btn btn-primary">Search / Refine</button></form>
<?php if($history):?><p class="small text-muted">Instructions: <?=e(implode(' → ',$history))?></p><?php endif;?><p><strong>Current filters:</strong> <?=e(($filters['category']??'All categories').' · '.implode(' + ',$filters['terms']??[]).' · '.(isset($filters['min_price'])?'from £'.$filters['min_price'].' ':'').(isset($filters['max_price'])?'up to £'.$filters['max_price']:''))?></p>
<div class="d-flex flex-wrap gap-2 mb-4"><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="reset"><button class="btn btn-outline-secondary">New search</button></form><form method="post" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input name="name" class="form-control" maxlength="120" required placeholder="Name this search"><button class="btn btn-outline-primary">Save search</button></form></div>
<h2 class="h4"><?=count($results['data'])?> results shown (<?=$results['meta']['total']?> total)</h2><?php if(!$results['data']):?><p>No matching adverts. Try a broader search or start again.</p><?php endif;?>
<div class="row g-3"><?php foreach($results['data'] as $item):?><div class="col-md-6 col-lg-4"><div class="card h-100"><div class="card-body"><h3 class="h5"><a href="browse.php?listing=<?=(int)$item['id']?>"><?=e($item['title'])?></a></h3><p>£<?=e(number_format((float)$item['price_gbp'],2))?> · <?=e($item['location']??'')?></p><small><?=e($item['category']['name'])?></small></div></div></div><?php endforeach;?></div></main></body></html>
