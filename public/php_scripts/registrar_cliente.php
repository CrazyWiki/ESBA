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

    $conn->begin_transaction();

    try {
        // Verificar si el usuario ya existe
        $stmtCheck = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE email = ?");
        $stmtCheck->bind_param("s", $email);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();

        if ($result->num_rows > 0) {
            // Ya existe: reutilizamos el ID
            $row = $result->fetch_assoc();
            $id_usuario = $row['id_usuario'];
        } else {
            // No existe: lo creamos
            $stmtInsertUser = $conn->prepare("INSERT INTO Usuarios (email, password_hash) VALUES (?, ?)");
            $stmtInsertUser->bind_param("ss", $email, $password_hash);
            $stmtInsertUser->execute();
            $id_usuario = $conn->insert_id;
        }

        // Verificar si ya está registrado como cliente
        $stmtCheckCliente = $conn->prepare("SELECT id_cliente FROM Clientes WHERE Usuarios_id_usuario = ?");
        $stmtCheckCliente->bind_param("i", $id_usuario);
        $stmtCheckCliente->execute();
        $resCliente = $stmtCheckCliente->get_result();

        if ($resCliente->num_rows > 0) {
            echo "Este usuario ya está registrado como cliente.";
        } else {
            // Insertar en Clientes
            $stmtCliente = $conn->prepare("INSERT INTO Clientes (
                nombre_cliente, apellido_cliente, telefono, direccion, codigo_postal,
                numero_documento, Usuarios_id_usuario
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtCliente->bind_param("ssssssi", $nombre, $apellido, $telefono, $direccion, $codigo_postal, $numero_documento, $id_usuario);
            $stmtCliente->execute();

            $conn->commit();
            echo "Cliente registrado correctamente.";
        }

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        echo "Error en el registro: " . $e->getMessage();
    }
} else {
    echo "Solicitud inválida.";
}
