<?php

session_start();
require_once '../server/database.php';

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

    // Validación del Lado del Servidor
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
    $stmt_check = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $errors[] = "Este correo electrónico ya está registrado.";
    }
    $stmt_check->close();
    
    if (!empty($errors)) {
        $response = ['success' => false, 'message' => implode('<br>', $errors)];
        echo json_encode($response);
        exit();
    }

    // Si la validación es exitosa, proceder con la inserción
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $conn->begin_transaction();

    try {
        $stmtUser = $conn->prepare("INSERT INTO Usuarios (email, password_hash,fecha_creacion) VALUES (?, ?, NOW())");
        $stmtUser->bind_param("ss", $email, $password_hash);
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
        // Mensaje genérico para el usuario
        $response = ['success' => false, 'message' => 'Error de base de datos. No se pudo completar el registro.'];
        // Registrar el error real para el administrador (opcional)
        error_log('Error de registro de cliente: ' . $e->getMessage());
    }
} else {
    $response = ['success' => false, 'message' => 'Solicitud inválida.'];
}

echo json_encode($response);
?>
