<?php
// Mostrar todos los errores para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
session_start();

// Incluir conexión a BD usando ruta absoluta con __DIR__
require_once __DIR__ . '/../../server/database.php';

// Verificar conexión
if (!$conn) {
    echo "Error en la conexión a la base de datos: " . mysqli_connect_error();
    exit;
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validar datos mínimos
    if (empty($email) || empty($password) || !in_array($tipo, ['cliente', 'empleado'])) {
        echo "Datos incompletos o inválidos.";
        exit;
    }

    // Preparar búsqueda del usuario
    $stmt = $conn->prepare("SELECT id_usuario, password_hash FROM Usuarios WHERE email = ?");
    if (!$stmt) {
        echo "Error en la consulta: " . $conn->error;
        exit;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Verificar si encontró usuario
    if ($stmt->num_rows !== 1) {
        echo "Correo electrónico no registrado.";
        exit;
    }

    $stmt->bind_result($id_usuario, $password_hash);
    $stmt->fetch();

    // Verificar contraseña
    if (!password_verify($password, $password_hash)) {
        echo "Contraseña incorrecta.";
        exit;
    }

    // Verificar rol (cliente o empleado)
    if ($tipo === 'cliente') {
        $rolStmt = $conn->prepare("SELECT Usuarios_id_usuario FROM Clientes WHERE Usuarios_id_usuario = ?");
    } else {
        $rolStmt = $conn->prepare("SELECT Usuarios_id_usuario FROM Empleados WHERE Usuarios_id_usuario = ?");
    }

    if (!$rolStmt) {
        echo "Error en la consulta del rol: " . $conn->error;
        exit;
    }

    $rolStmt->bind_param("i", $id_usuario);
    $rolStmt->execute();
    $rolStmt->store_result();

    if ($rolStmt->num_rows !== 1) {
        echo "El usuario no tiene el rol solicitado.";
        exit;
    }

    // Login exitoso
    $_SESSION['usuario_id'] = $id_usuario;
    $_SESSION['usuario_email'] = $email;
    $_SESSION['usuario_rol'] = $tipo;

    echo "Login exitoso. Redireccionando...";
} else {
    echo "Acceso inválido.";
}

// Cerrar conexiones
if (isset($stmt)) $stmt->close();
if (isset($rolStmt)) $rolStmt->close();
$conn->close();
?>
