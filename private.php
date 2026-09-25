<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_login();

$name = (string)($_SESSION['auth_name'] ?? '');
$email = (string)($_SESSION['auth_email'] ?? '');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SkyMart - My account</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body><main class="container py-5"><div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="mb-1">SkyMart</h1><p class="text-muted mb-0">Signed in as <?= e($name !== '' ? $name : $email) ?></p></div>
<form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-outline-secondary" type="submit">Log out</button></form></div>
<div class="alert alert-info">Secure account foundation is active. Marketplace listing management arrives in Phase 3.</div></main></body></html>
