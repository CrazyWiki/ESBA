<?php
$servername = "localhost";
$username = "esba_user";
$password = "esba123";  // la que elegiste
$dbname = "esbaproj";   // asegurate que exista esta base

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
