<?php
// Forzar la visualización de errores de PHP (útil para la depuración)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start(); // Iniciar sesión para manejar mensajes de error

// El path está a dos niveles de la raíz, por lo que '../../' es correcto
require_once '../../server/database.php';

// Establecer la cabecera para devolver siempre JSON
header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo']) && $_POST['tipo'] === 'cliente') {
    $nombre = trim($_POST['nombre_cliente']);
    $apellido = trim($_POST['apellido_cliente']);
    $telefono = trim($_POST['telefono']) ?? null;
    $direccion = trim($_POST['direccion']) ?? null;
    $codigo_postal = trim($_POST['codigo_postal']) ?? null;
    $numero_documento = trim($_POST['numero_documento']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // --- Validación del Lado del Servidor ---
    $errors = [];
    if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($numero_documento)) {
        $errors[] = "Todos los campos obligatorios deben ser completados.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del correo electrónico no es válido.";
    }
    if (strlen($password) < 8) {
        $errors[] = "La contraseña debe tener al menos 8 caracteres.";
    }
    // Verificar si el email ya existe
    $stmt = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Este correo electrónico ya está registrado.";
    }
    $stmt->close();
    
    if (!empty($errors)) {
        $response = ['success' => false, 'message' => implode('<br>', $errors)];
        echo json_encode($response);
        exit();
    }
    // --- Fin de la Validación ---

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $conn->begin_transaction();

    try {
        $stmtUser = $conn->prepare("INSERT INTO Usuarios (email, password_hash) VALUES (?, ?)");
        $stmtUser->bind_param("ss", $email, $password_hash); // Variable $email corregida
        $stmtUser->execute();
        $id_usuario = $conn->insert_id;
        $stmtUser->close();

        $stmtCliente = $conn->prepare("INSERT INTO Clientes (nombre_cliente, apellido_cliente, telefono, direccion, codigo_postal, numero_documento, Usuarios_id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtCliente->bind_param("ssssssi", $nombre, $apellido, $telefono, $direccion, $codigo_postal, $numero_documento, $id_usuario);
        $stmtCliente->execute();
        $stmtCliente->close();

        $conn->commit();
        $response = ['success' => true, 'message' => '¡Registro exitoso! Redirigiendo...'];
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $response = ['success' => false, 'message' => 'Error de base de datos. No se pudo completar el registro.'];
        // Para depuración: error_log($e->getMessage());
    }
} else {
    $response = ['success' => false, 'message' => 'Solicitud inválida.'];
}

echo json_encode($response);
?>