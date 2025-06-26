<?php
// server/database.php
//$servername = "sql312.infinityfree.com";
//$username = "if0_39187801";
//$password = "7ipLWY99Df5kSY";
//$dbname = "if0_39187801_database";
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "esbaproj";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn && !$conn->connect_error) {
    $conn->set_charset("utf8mb4");
}
?>