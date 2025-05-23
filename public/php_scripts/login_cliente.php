<?php
require_once '../../server/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Buscar usuario por email
    $stmt = $conn->prepare("SELECT id_usuario, password_hash FROM Usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Verifica si existe el usuario
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id_usuario, $password_hash);
        $stmt->fetch();

        // Verificar contraseña
        if (password_verify($password, $password_hash)) {
            $_SESSION['usuario_id'] = $id_usuario;
            $_SESSION['usuario_email'] = $email;
            echo "Login exitoso. Redireccionando...";
            // Redirigir al área personal
            header("Location: ../area_personal.php");
            exit();
        } else {
            echo "Contraseña incorrecta.";
        }
    } else {
        echo "Correo electrónico no registrado.";
    }

    $stmt->close();
} else {
    echo "Acceso inválido.";
}

$conn->close();
?>