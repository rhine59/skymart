<?php
include 'lib/library.php';
include 'lib/database_connection.php';
 
$login_error_message = '';
$register_error_message = '';
$register_success_message = '';
$app = '';
 
// check Register request
if (!empty($_POST['btnRegister'])) {
    // validated user input
    if ($_POST['first_name'] == "") {
        $register_error_message = 'First name field is required!';
    } else if ($_POST['last_name'] == "") {
        $register_error_message = 'Last name field is required!';
    } else if ($_POST['email'] == "") {
        $register_error_message = 'Email field is required!';
    } else if ($_POST['password'] == "") {
        $register_error_message = 'Password field is required!';
    } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $register_error_message = 'Invalid email address!';
    } else if ($app->isEmail($_POST['email'])) {
        $register_error_message = 'Email is already in use!';
    } else {
        if ($app->Register($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['password'])) {
            // show success message and ask user to check email for verification link
            $register_success_message = 'Your account is created successfully, please check your email for verification link to activate your account.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="lib/bootstrap-4.4.1-dist/css/bootstrap.min.css">
</head>
<body>
 
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2>
                SkyMart user Registration and Login
            </h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-5 well">
            <br>
            <h4>Register</h4>
 
            <form action="index.php" method="post">
                <div class="form-group">
                    <label for="">First Name</label>
                    <input type="text" name="first_name" class="form-control" placeholder="First Name"/>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Last Name"/>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="text" id="email" name="email" class="form-control" placeholder="Email Address"/>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password"/>
                </div>
                <div class="form-group">
                    <input type="submit" name="btnRegister" class="btn btn-primary" value="Register"/>
                </div>
            </form>
        </div>
        <div class="col-md-2"></div>
        <div class="col-md-5 well">
            <br>
            <h4>Login</h4>
 
            <form action="index.php" method="post">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Email Address"/>
                </div>
                <div class="form-group">
                    <label for="">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password"/>
                </div>
                <div class="form-group">
                    <input type="submit" name="btnLogin" class="btn btn-primary" value="Login"/>
                </div>
            </form>
        </div>
    </div>
</div>
 
</body>
</html>