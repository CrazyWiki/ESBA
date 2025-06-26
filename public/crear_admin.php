<?php
require_once '../server/database.php'; 
$email = 'admin@gmail.com';
$password = 'admin123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO Administradores (email, password, role) VALUES (?, ?, 'Administrador')");
$stmt->bind_param("ss", $email, $password_hash);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    echo "Administrador creado correctamente.";
} else {
    echo "Error al crear administrador: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
