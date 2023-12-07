<?php  

include 'database.php';



$op = $_POST['op'];
$userid = $_POST['userid'];
$password = $_POST['password'];

echo "<br><br>op: $op  userid: $userid  password: $password";

if ($op=="login")
{
    // $sql = "SELECT name FROM users WHERE name='$userid' AND PASSWORD('$password') = password";

    
    $sql = "SELECT name FROM users WHERE name='$userid'";

    $result = mysqli_query($link,$sql);

    // FAILED LOGIN
    if (mysqli_num_rows($result)==0)
    {
         echo "<p>Nothing found here</p>";
         $failed=1;
    }

    // SUCCESS LOGIN
    else
    {
        echo "<p>Found! Login OK!</p>";
        $row = $result->fetch_row();

        $db_id = $row[0];
        $db_name = $row[1];

        echo "<br><br>ID: $db_id  Name: $db_name";

        setcookie("auth_id","$db_id");
        setcookie("auth_email","$email");

        //echo "Success! Cookie value: " . $_COOKIE['auth_id'];
        header("Location:private.php");

        exit;
    }



}


?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Skymart - Login or Register</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
        rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" 
        crossorigin="anonymous">
	<link rel="stylesheet" href="register.css">
</head>
<body>
  <section class="vh-100 bg-image" style="background-image: url('images/selling.jpg');">
  <div class="mask d-flex align-items-center h-100 gradient-custom-3">
    <div class="container h-100">
      <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-9 col-lg-7 col-xl-6">
          <div class="card" style="border-radius: 15px;">
            <div class="card-body p-5">
              <h2 class="text-center mb-5">SkyMart Login</h2>

              <form method=POST action="index.php">

                <div class="form-outline mb-4">
                  <input type="text" id="id-index-1" class="form-control form-control-lg" required name="userid"/>
                  <label class="form-label" for="label-userid">User ID</label>
                </div>
                <div class="form-outline mb-4">
                  <input type="password" id="id-index-2" class="form-control form-control-lg" required name="password"/>
                  <label class="form-label" for="label-userid">Password</label>
                </div>
                <div class="form-check d-flex justify-content-center mb-5">
                  <input class="form-check-input me-2" type="checkbox" value="" id="id-index-3"/>
                  <label class="form-check-label" for="label-userid">
                    I agree to the <a href="#!" class="text-body"><u>Terms of service</u></a>
                  </label>
                </div>

                <div class="d-flex justify-content-center">
                  <button type="submit" name="op" value="login"
                    class="btn btn-success btn-block mb-lg-1">Login
                  </button>
                </div>
                
                <br>
                <div class="d-flex justify-content-center">
                  <button type="submit" name="op" value="register"
                    class="btn btn-warning btn-block mb-lg-1">Register
                  </button>
                </div>
                
              </form>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>




</body>
</html>
