<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/src/ListingService.php';
require_once dirname(__DIR__) . '/src/PersonalMarketplaceService.php';
$service = new ListingService($link);
$categories = $service->categories();
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120);
$category = mb_substr(trim((string)($_GET['category'] ?? '')), 0, 80);
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page === false ? 1 : max(1, min($page, 100000));
$selected = null;
foreach ($categories as $item) {
    if ($item['slug'] === $category) $selected = $item;
}
if ($selected === null) $category = '';
$results = $service->search($q, $category, $page, 20);
$savedSearches=[];
if(current_user_id()!==null){$savedSearches=(new PersonalMarketplaceService($link,$service))->searches((int)current_user_id());}
$detailId = filter_var($_GET['listing'] ?? null, FILTER_VALIDATE_INT);
$detail = $detailId && $detailId > 0 ? $service->find($detailId) : null;
function browse_link(array $params): string {
    return 'browse.php?' . http_build_query(array_filter($params, static fn($v) => $v !== '' && $v !== null));
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SkyMart | Aviation marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/marketplace.css"></head>
<body>
<header class="border-bottom bg-white"><nav class="container navbar navbar-expand py-3">
<a class="navbar-brand fw-bold fs-3" href="index.php">✈ SkyMart</a>
<div class="ms-auto d-flex gap-2"><a class="btn btn-outline-primary" href="browse.php">Buy</a><a class="btn btn-primary" href="sell.php">Sell</a>
<?php if (current_user_id() !== null): ?><a class="btn btn-outline-primary" href="saved.php">Saved</a><a class="btn btn-outline-primary" href="private.php">My account</a>
<?php else: ?><a class="btn btn-outline-primary" href="login.php">Sign in</a><a class="btn btn-primary" href="register.php">Register</a><?php endif; ?>
</div></nav></header>
<main class="container py-4">
<?php if ($detail !== null): ?>
<a href="<?= e(browse_link(['q'=>$q,'category'=>$category,'page'=>$page])) ?>" class="d-inline-block mb-3">← Back to adverts</a>
<article class="card shadow-sm border-0 p-4">
<div class="row g-4"><div class="col-md-6">
<?php if ($detail['images']): ?>
<img class="detail-image rounded" src="<?= e($detail['images'][0]['url']) ?>" alt="<?= e($detail['title']) ?>">
<div class="d-flex flex-wrap gap-2 mt-2"><?php foreach ($detail['images'] as $image): ?>
<a href="<?= e($image['url']) ?>" target="_blank" rel="noopener noreferrer"><img class="detail-thumb rounded" src="<?= e($image['thumbnail_url']) ?>" alt="Advert photograph"></a>
<?php endforeach; ?></div>
<?php else: ?><div class="photo-placeholder detail-image rounded">✈</div><?php endif; ?>
</div><div class="col-md-6">
<span class="badge text-bg-light mb-2"><?= e($detail['category']['name']) ?></span>
<h1><?= e($detail['title']) ?></h1><p class="display-6 fw-bold">£<?= e(number_format((float)$detail['price_gbp'], 2)) ?></p>
<p class="text-muted">📍 <?= e($detail['location'] ?? '') ?></p>
<h2 class="h5">Description</h2><p class="description"><?= e($detail['description']) ?></p>
<p class="text-muted">Seller: <?= e($detail['seller']['name']) ?></p>
<?php if(current_user_id()!==null && (int)current_user_id()!==(int)$detail['seller']['id']):?><form method="post" action="enquiries.php" class="mt-3"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="listing_id" value="<?=(int)$detail['id']?>"><label class="form-label w-100">Enquire about buying<textarea name="message" class="form-control" minlength="10" maxlength="2000" rows="3" required placeholder="Ask the seller about availability, condition or viewing arrangements"></textarea></label><button class="btn btn-primary">Send enquiry</button></form><?php elseif(current_user_id()===null):?><p><a href="login.php">Sign in to contact the seller</a></p><?php endif;?>
<?php if(current_user_id()!==null):?><form method="post" action="saved.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="favourite"><input type="hidden" name="id" value="<?=(int)$detail['id']?>"><button class="btn btn-outline-danger">♡ Add to favourites</button></form><?php endif;?>
</div></div></article>
<?php else: ?>
<section class="hero rounded-4 p-4 p-md-5 mb-4">
<p class="text-uppercase small fw-bold mb-2">The aviation marketplace</p>
<h1 class="display-5 fw-bold">Find your next aviation adventure</h1>
<p class="mb-0">Aircraft · Avionics · Parts · Pilot equipment</p></section>
<form class="row g-2 mb-3" method="get" action="browse.php" role="search">
<div class="col-9 col-md-10"><label for="q" class="visually-hidden">Search adverts</label><input id="q" name="q" class="form-control form-control-lg" value="<?= e($q) ?>" placeholder="Search aircraft, avionics, parts…"><input type="hidden" name="category" value="<?=e($category)?>"></div>
<div class="col-3 col-md-2"><button class="btn btn-primary btn-lg w-100" type="submit">Search</button></div></form>
<div class="d-flex flex-wrap gap-2 mb-4" aria-label="Advert categories"><a class="btn <?= $category===''?'btn-primary':'btn-outline-primary' ?>" href="<?=e(browse_link(['q'=>$q]))?>">All categories</a><?php foreach($categories as $item):?><a class="btn <?= $category===$item['slug']?'btn-primary':'btn-outline-primary' ?>" href="<?=e(browse_link(['q'=>$q,'category'=>$item['slug']]))?>"><?=e($item['name'])?></a><?php endforeach;?></div>
<?php if(current_user_id()!==null):?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-4"><strong>My saved searches:</strong><?php foreach($savedSearches as $saved):?><a class="btn btn-sm btn-outline-secondary" href="<?=e(browse_link(['q'=>$saved['query_text'],'category'=>$saved['category_slug']]))?>"><?=e($saved['name'])?></a><?php endforeach;?><a href="saved.php" class="btn btn-sm btn-outline-primary">Manage saved searches</a><form action="saved.php" method="post" class="d-inline-flex gap-2"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="query_text" value="<?=e($q)?>"><input type="hidden" name="category_slug" value="<?=e($category)?>"><input type="hidden" name="frequency" value="daily"><input type="hidden" name="enabled" value="1"><input class="form-control form-control-sm" name="name" maxlength="120" required placeholder="Name this search"><button class="btn btn-sm btn-primary" type="submit">Save filters</button></form></div>
<?php endif;?>
<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h4 mb-0">Latest adverts</h2><span class="text-muted"><?= (int)$results['meta']['total'] ?> adverts</span></div>
<?php if (!$results['data']): ?><div class="alert alert-light border">No adverts found. Try another search or category.</div><?php endif; ?>
<div class="row g-3"><?php foreach ($results['data'] as $listing): ?>
<div class="col-12 col-sm-6 col-lg-4"><a class="card listing-card h-100 text-decoration-none text-body" href="<?= e(browse_link(['listing'=>$listing['id'],'q'=>$q,'category'=>$category,'page'=>$page])) ?>">
<?php if ($listing['images']): ?><img class="card-img-top listing-image" src="<?= e($listing['images'][0]['thumbnail_url']) ?>" alt="<?= e($listing['title']) ?>" loading="lazy">
<?php else: ?><div class="photo-placeholder listing-image">✈</div><?php endif; ?>
<div class="card-body"><span class="small text-primary"><?= e($listing['category']['name']) ?></span>
<h3 class="h5 mt-1"><?= e($listing['title']) ?></h3><p class="h4 fw-bold">£<?= e(number_format((float)$listing['price_gbp'],2)) ?></p>
<p class="text-muted mb-0 small">📍 <?= e($listing['location'] ?? '') ?></p></div></a></div>
<?php endforeach; ?></div>
<nav class="d-flex justify-content-between mt-4" aria-label="Adverts pages">
<?php if ($page > 1): ?><a class="btn btn-outline-primary" href="<?= e(browse_link(['q'=>$q,'category'=>$category,'page'=>$page-1])) ?>">← Previous</a><?php else: ?><span></span><?php endif; ?>
<?php if ($page * 20 < $results['meta']['total']): ?><a class="btn btn-outline-primary" href="<?= e(browse_link(['q'=>$q,'category'=>$category,'page'=>$page+1])) ?>">Next →</a><?php endif; ?></nav>
<?php endif; ?>
</main></body></html>
