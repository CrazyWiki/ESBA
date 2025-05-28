<?php
$servername = "localhost";
$username = "root";
$password = ""; // o la contraseña que uses
$dbname = "esbaproj";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Fallo la conexión: " . $conn->connect_error);
}
echo "✅ Conexión exitosa a la base de datos!";
?>
