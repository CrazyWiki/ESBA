<?php
// server/database.php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "esbaproj";

$conn = new mysqli($servername, $username, $password, $dbname);

// Попытка создать соединение
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn && !$conn->connect_error) {
    $conn->set_charset("utf8mb4");
}
?>