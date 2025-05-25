<?php
require_once '../../server/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo']) && $_POST['tipo'] === 'cliente') {
    $nombre = trim($_POST['nombre_cliente']);
    $apellido = trim($_POST['apellido_cliente']);
    $telefono = !empty($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $direccion = !empty($_POST['direccion']) ? trim($_POST['direccion']) : null;
    $codigo_postal = !empty($_POST['codigo_postal']) ? trim($_POST['codigo_postal']) : null;
    $numero_documento = trim($_POST['numero_documento']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($nombre) || empty($apellido) || empty($numero_documento) || empty($email) || empty($password)) {
        exit("Por favor complete todos los campos obligatorios.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Correo electrónico no válido.");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {
        $stmtUser = $conn->prepare("INSERT INTO Usuarios (email, password_hash) VALUES (?, ?)");
        if (!$stmtUser) {
            throw new Exception("Error al preparar consulta de Usuarios: " . $conn->error);
        }
        $stmtUser->bind_param("ss", $email, $password_hash);
        $stmtUser->execute();

        $id_usuario = $conn->insert_id;

        $stmtCliente = $conn->prepare("INSERT INTO Clientes (
            nombre_cliente, apellido_cliente, telefono, direccion, codigo_postal,
            numero_documento, Usuarios_id_usuario
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmtCliente) {
            throw new Exception("Error al preparar consulta de Clientes: " . $conn->error);
        }
        $stmtCliente->bind_param("ssssssi", $nombre, $apellido, $telefono, $direccion, $codigo_postal, $numero_documento, $id_usuario);
        $stmtCliente->execute();

        $conn->commit();
        echo "Cliente registrado correctamente.";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error en el registro: " . $e->getMessage();
    }
} else {
    echo "Solicitud inválida.";
}
?>
