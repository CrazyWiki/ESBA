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

        // Insertar en Empleados (verificamos si ya existe también ahí)
        $stmtCheckEmp = $conn->prepare("SELECT id_empleado FROM Empleados WHERE Usuarios_id_usuario = ?");
        $stmtCheckEmp->bind_param("i", $id_usuario);
        $stmtCheckEmp->execute();
        $resEmp = $stmtCheckEmp->get_result();

        if ($resEmp->num_rows > 0) {
            echo "Este usuario ya está registrado como empleado.";
        } else {
            $stmtEmpleado = $conn->prepare("INSERT INTO Empleados (nombre, cargo, Usuarios_id_usuario) VALUES (?, ?, ?)");
            $stmtEmpleado->bind_param("ssi", $nombre, $cargo, $id_usuario);
            $stmtEmpleado->execute();
            $conn->commit();
            echo "Empleado registrado correctamente.";
        }

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        echo "Error en el registro: " . $e->getMessage();
    }
} else {
    echo "Solicitud inválida.";
}
