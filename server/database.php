<?php

$servername = "localhost"; 
$username = "esba_user";        
$password = "1234";            
$dbname = "esbaproj";      

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");

?>