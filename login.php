<?php

    echo "<h1>Hello mum</h1>";
    $op = $_POST['op'];
    $email = $_POST['email'];
    $pass = $_POST['pass'];

    if ($op=="login")
    {
        $sql = "SELECT id,name FROM users WHERE email='$email' AND PASSWORD('$pass') = password";
        $result = mysqli_query($link,$sql);

        // FAILED LOGIN
        if (mysqli_num_rows($result)==0)
        {
             //echo "Nothing found here";
             $failed=1;
        }

        // SUCCESS LOGIN
        else
        {
            //echo "Found! Login OK!";
            $row = $result->fetch_row();

            $db_id = $row[0];
            $db_name = $row[1];

            //echo "<br><br>ID: $db_id  Name: $db_name";

            setcookie("auth_id","$db_id");
            setcookie("auth_email","$email");

            //echo "Success! Cookie value: " . $_COOKIE['auth_id'];
            header("Location:private.php");

            exit;
        }



    }

?>

<!DOCTYPE HTML>

<html>

<head>
    <title>Login</title>

            <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

        <!-- jQuery library -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

        <!-- Latest compiled JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

</head>

<body>



<div class="container p-3 col-md-4">

    <?php
    if ($failed==1)
    {
    ?>
              <div class="panel panel-danger">
                <div class="panel-heading">Login error</div>
                <div class="panel-body">Wrong login or password</div>
              </div>

    <?php
    }
    ?>

    <div class="panel panel-default">
    <div class="panel-heading">Login page</div>
    <div class="panel-body">


        <form method=POST action=login.php>

            <div class="form-group">
                <label>Email:</label>
                <input type="text" class="form-control" autocomplete=off required name="email"/>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" class="form-control"  required name="pass"/>
            </div>


            <div class="form-group">
                <label></label>
                <input type="submit" class="btn btn-primary" value="Login"/>
            </div>

            <input type="hidden" name="op" value="login" />

        </form>
    </div>
    </div>
</div>

</body>

</html>