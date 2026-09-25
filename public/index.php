<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

if (current_user_id() !== null) { header('Location: private.php'); exit; }
$failed=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
 require_csrf(); $email=trim((string)($_POST['email']??'')); $password=(string)($_POST['password']??'');
 $stmt=mysqli_prepare($link,'SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1');
 mysqli_stmt_bind_param($stmt,'s',$email); mysqli_stmt_execute($stmt); $user=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
 if ($user && password_verify($password,$user['password'])) { session_regenerate_id(true); $_SESSION['auth_id']=(int)$user['id']; $_SESSION['auth_email']=$user['email']; $_SESSION['auth_name']=$user['name']; header('Location: private.php'); exit; }
 $failed=true;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SkyMart - Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/register.css"></head><body><section class="vh-100 bg-image" style="background-image:url('images/selling.jpg')"><div class="mask d-flex align-items-center h-100 gradient-custom-3"><div class="container"><div class="row justify-content-center"><div class="col-12 col-md-8 col-lg-6"><div class="card"><div class="card-body p-5"><h1 class="h2 text-center mb-4">SkyMart</h1><p class="text-center text-muted">Buying and selling all things aeronautical</p><?php if($failed): ?><div class="alert alert-danger">Invalid email or password.</div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="mb-3"><label class="form-label" for="email">Email</label><input id="email" type="email" name="email" class="form-control" required autocomplete="username"></div><div class="mb-3"><label class="form-label" for="password">Password</label><input id="password" type="password" name="password" class="form-control" required autocomplete="current-password"></div><button class="btn btn-success w-100" type="submit">Login</button></form><p class="text-center mt-4 mb-0">New to SkyMart? <a href="register.php">Create an account</a></p></div></div></div></div></div></div></section></body></html>
