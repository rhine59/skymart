<?php

$host = 'localhost';
$uid = 'root';
$pwd = 'granv1lle';
$db = 'skymart';
$email = 'rhine59@gmail.com';

$con = new mysqli($host, $uid, $pwd, $db);
 
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
 
$email = $con->real_escape_string($email);

$query = "SELECT `email` FROM `users` WHERE `email` = '$email'";

if (!$result = $con->query($query)) {
    printf("Select returned %d rows.\n", $result->num_rows);
    $result->close();      
}

printf("HELLO Select returned %s rows.\n", $email);

?>