<?php
// Database credentials must be supplied by the hosting environment.
// Never commit production credentials to this repository.
$dbHost = getenv('SKYMART_DB_HOST') ?: 'localhost';
$dbName = getenv('SKYMART_DB_NAME') ?: 'skymart';
$dbUser = getenv('SKYMART_DB_USER');
$dbPass = getenv('SKYMART_DB_PASSWORD');

if (!$dbUser || $dbPass === false) {
    http_response_code(500);
    exit('Database configuration is missing.');
}

$link = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$link) {
    error_log('SkyMart database connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    exit('Database connection failed.');
}
mysqli_set_charset($link, 'utf8mb4');
?>