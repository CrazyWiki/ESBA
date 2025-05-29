<?php

$servername = "localhost"; 
$username = "root";        
$password = "";            
$dbname = "esbaproj";
$port = 3307;      

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");

?>