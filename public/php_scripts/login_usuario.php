<?php
require_once '../../server/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password) || !in_array($tipo, ['cliente', 'empleado'])) {
        echo "Datos incompletos o inválidos.";
        exit;
    }

    // Buscar usuario
    $stmt = $conn->prepare("SELECT id_usuario, password_hash FROM Usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows !== 1) {
        echo "Correo electrónico no registrado.";
        exit;
    }

    $stmt->bind_result($id_usuario, $password_hash);
    $stmt->fetch();

    if (!password_verify($password, $password_hash)) {
        echo "Contraseña incorrecta.";
        exit;
    }

    // Verificar rol según tipo solicitado
    if ($tipo === 'cliente') {
        $rolStmt = $conn->prepare("SELECT Usuarios_id_usuario FROM Clientes WHERE Usuarios_id_usuario = ?");
    } else {
        $rolStmt = $conn->prepare("SELECT Usuarios_id_usuario FROM Empleados WHERE Usuarios_id_usuario = ?");
    }
    $rolStmt->bind_param("i", $id_usuario);
    $rolStmt->execute();
    $rolStmt->store_result();

    if ($rolStmt->num_rows !== 1) {
        echo "El usuario no tiene el rol solicitado.";
        exit;
    }

    // Login exitoso, seteo sesión
    $_SESSION['usuario_id'] = $id_usuario;
    $_SESSION['usuario_email'] = $email;
    $_SESSION['usuario_rol'] = $tipo;

    echo "Login exitoso. Redireccionando...";
} else {
    echo "Acceso inválido.";
}
