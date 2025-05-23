<?php
require_once '../../server/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['tipo'] === 'cliente') {
    $nombre = $_POST['nombre_cliente'];
    $apellido = $_POST['apellido_cliente'];
    $telefono = $_POST['telefono'] ?? null;
    $direccion = $_POST['direccion'] ?? null;
    $codigo_postal = $_POST['codigo_postal'] ?? null;
    $numero_documento = $_POST['numero_documento'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Correo electrónico no válido.");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Empezar transacción
    $conn->begin_transaction();

    try {
        // Insertar en Usuarios
        $stmtUser = $conn->prepare("INSERT INTO Usuarios (email, password_hash) VALUES (?, ?)");
        $stmtUser->bind_param("ss", $email, $password_hash);
        $stmtUser->execute();

        $id_usuario = $conn->insert_id;

        // Insertar en Clientes
        $stmtCliente = $conn->prepare("INSERT INTO Clientes (
            nombre_cliente, apellido_cliente, telefono, direccion, codigo_postal,
            numero_documento, Usuarios_id_usuario
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtCliente->bind_param("ssssssi", $nombre, $apellido, $telefono, $direccion, $codigo_postal, $numero_documento, $id_usuario);
        $stmtCliente->execute();

        $conn->commit();
        echo "Cliente registrado correctamente.";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        echo "Error en el registro: " . $e->getMessage();
    }
} else {
    echo "Solicitud inválida.";
}
?>