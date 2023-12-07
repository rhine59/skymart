<?php

$id =  $_COOKIE['auth_id'];
$email = $_COOKIE['auth_email'];

$sql = "SELECT id FROM users WHERE id='$id' AND email='$email'";
$result = mysqli_query($link,$sql);

if (mysqli_num_rows($result)==0)
{
    echo "Unauthorized access!";
    exit;
}


?>