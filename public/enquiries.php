<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__).'/src/ListingService.php';
require_login();
$userId=(int)current_user_id();$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 require_csrf();
 $id=filter_var($_POST['listing_id']??null,FILTER_VALIDATE_INT);
 $message=trim((string)($_POST['message']??''));
 $listing=$id?(new ListingService($link))->find($id):null;
 if(!$listing) $error='Advert unavailable.';
 elseif((int)$listing['seller']['id']===$userId) $error='You cannot enquire about your own advert.';
 elseif(mb_strlen($message)<10 || mb_strlen($message)>2000) $error='Message must contain 10–2000 characters.';
 else {
  $sellerId=(int)$listing['seller']['id'];
  $stmt=$link->prepare('INSERT INTO buyer_enquiries (listing_id,buyer_id,seller_id,message) VALUES (?,?,?,?)');
  $stmt->bind_param('iiis',$id,$userId,$sellerId,$message);$stmt->execute();
  header('Location: enquiries.php?sent=1',true,303);exit;
 }
}
$stmt=$link->prepare('SELECT e.id,e.message,e.created_at,l.id listing_id,l.title,u.name buyer_name FROM buyer_enquiries e JOIN listings l ON l.id=e.listing_id JOIN users u ON u.id=e.buyer_id WHERE e.seller_id=? ORDER BY e.created_at DESC LIMIT 100');
$stmt->bind_param('i',$userId);$stmt->execute();$received=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt=$link->prepare('SELECT e.id,e.message,e.created_at,l.id listing_id,l.title FROM buyer_enquiries e JOIN listings l ON l.id=e.listing_id WHERE e.buyer_id=? ORDER BY e.created_at DESC LIMIT 100');
$stmt->bind_param('i',$userId);$stmt->execute();$sent=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Enquiries | SkyMart</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body><main class="container py-4"><nav><a href="browse.php">Buy</a> · <a href="sell.php">Sell</a> · <a href="private.php">Account</a></nav><h1 class="mt-3">Purchase enquiries</h1><p>Messages are stored securely in your SkyMart account. No payment or sale is completed by sending an enquiry.</p><?php if(isset($_GET['sent'])):?><div class="alert alert-success">Your enquiry was sent to the seller's SkyMart inbox.</div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><h2 class="h4 mt-4">Received from buyers</h2><?php if(!$received):?><p>No enquiries received.</p><?php endif;?><?php foreach($received as $item):?><article class="border-bottom py-3"><strong><?=e($item['title'])?></strong><p class="mb-1">From <?=e($item['buyer_name'])?> · <?=e($item['created_at'])?></p><p class="mb-0"><?=nl2br(e($item['message']))?></p></article><?php endforeach;?><h2 class="h4 mt-4">Sent to sellers</h2><?php if(!$sent):?><p>No enquiries sent.</p><?php endif;?><?php foreach($sent as $item):?><article class="border-bottom py-3"><strong><?=e($item['title'])?></strong><p class="mb-1"><?=e($item['created_at'])?></p><p><?=nl2br(e($item['message']))?></p></article><?php endforeach;?></main></body></html>
