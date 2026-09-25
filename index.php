<?php
include 'database.php';

$failed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'login') {
    $userid = trim($_POST['userid'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($link, 'SELECT id, name, email, password FROM users WHERE name = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $userid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    // Supports modern password_hash() values. Legacy MySQL PASSWORD() hashes must be reset/migrated.
    if ($user && password_verify($password, $user['password'])) {
        session_start();
        session_regenerate_id(true);
        $_SESSION['auth_id'] = (int)$user['id'];
        $_SESSION['auth_email'] = $user['email'];
        header('Location: private.php');
        exit;
    }
    $failed = true;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SkyMart - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="register.css">
</head>
<body>
<section class="vh-100 bg-image" style="background-image: url('images/selling.jpg');">
<div class="mask d-flex align-items-center h-100 gradient-custom-3"><div class="container h-100">
<div class="row d-flex justify-content-center align-items-center h-100"><div class="col-12 col-md-9 col-lg-7 col-xl-6">
<div class="card" style="border-radius:15px"><div class="card-body p-5">
<h2 class="text-center mb-5">SkyMart Login</h2>
<?php if ($failed): ?><div class="alert alert-danger">Invalid user ID or password.</div><?php endif; ?>
<form method="post" action="index.php">
<div class="form-outline mb-4"><input type="text" class="form-control form-control-lg" required name="userid" autocomplete="username"><label class="form-label">User ID</label></div>
<div class="form-outline mb-4"><input type="password" class="form-control form-control-lg" required name="password" autocomplete="current-password"><label class="form-label">Password</label></div>
<div class="d-flex justify-content-center"><button type="submit" name="op" value="login" class="btn btn-success">Login</button></div>
</form>
</div></div></div></div></div></div>
</section>
</body>
</html>
