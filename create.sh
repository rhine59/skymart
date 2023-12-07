<?php
$mysqli = new mysqli("localhost", "root", "VanessaH1ne123!", "aeromart");

$query = "SHOW TABLES";
if ($result = $mysqli->query($query)) {
    while ($row = $result->fetch_row()) {
        printf("%s <br />\n", $row[0]);
    }
    $result->close();
}

$mysqli->close();

?>