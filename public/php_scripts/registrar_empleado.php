<?php
require_once '../../server/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['tipo'] === 'empleado') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $cargo = $_POST['cargo'];
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Correo electrónico no válido.");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {
        // Insertar en Usuarios
        $stmtUser = $conn->prepare("INSERT INTO Usuarios (email, password_hash) VALUES (?, ?)");
        $stmtUser->bind_param("ss", $email, $password_hash);
        $stmtUser->execute();

        $id_usuario = $conn->insert_id;

        // Insertar en Empleados
        $stmtEmpleado = $conn->prepare("INSERT INTO Empleados (nombre, email, cargo, Usuarios_id_usuario) VALUES (?, ?, ?, ?)");
        $stmtEmpleado->bind_param("sssi", $nombre, $email, $cargo, $id_usuario);
        $stmtEmpleado->execute();

        $conn->commit();
        echo "Empleado registrado correctamente.";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        echo "Error en el registro: " . $e->getMessage();
    }
} else {
    echo "Solicitud inválida.";
}
?>
