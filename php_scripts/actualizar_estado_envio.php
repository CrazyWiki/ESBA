<?php
// Archivo: public/php_scripts/actualizar_estado_envio.php
// Propósito: Actualiza el estado de un envío.
// Utilizado por: area_personal_conductor.php, area_personal_gerente.php

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once '../server/database.php';

    $id_envio = $_POST['id_envio'] ?? 0;
    $nuevo_estado_desc = $_POST['nuevo_estado'] ?? '';
    $id_administrador = $_SESSION['user_id'];

    $id_conductor = null;
    // Si el usuario es 'conductor', obtenemos su conductor_id y aplicamos filtro de propiedad
    if (strtolower($_SESSION['user_role']) === 'conductor') { // Usa el rol de la sesión
        $stmt_conductor = $conn->prepare("SELECT conductor_id FROM `Conductores` WHERE Administradores_idAdministradores = ?");
        $stmt_conductor->bind_param("i", $id_administrador);
        $stmt_conductor->execute();
        $result_conductor = $stmt_conductor->get_result();
        if ($conductor_row = $result_conductor->fetch_assoc()) {
            $id_conductor = $conductor_row['conductor_id'];
        }
        $stmt_conductor->close();

        if ($id_conductor === null) {
            $_SESSION['update_status'] = "Error: No se encontró el perfil de conductor asociado a su cuenta.";
            // Dirige al conductor a su propia página, no al login
            header("Location: ../area_personal_conductor.php");
            exit();
        }
    }

    // Obtener ID del nuevo estado
    $id_nuevo_estado = null;
    $stmt_estado = $conn->prepare("SELECT estado_envio_id FROM `EstadoEnvio` WHERE descripcion = ?");
    $stmt_estado->bind_param("s", $nuevo_estado_desc);
    $stmt_estado->execute();
    $result_estado = $stmt_estado->get_result();
    if ($estado_row = $result_estado->fetch_assoc()) {
        $id_nuevo_estado = $estado_row['estado_envio_id'];
    }
    $stmt_estado->close();

    if ($id_envio > 0 && $id_nuevo_estado !== null) {
        // SQL para actualizar el estado del envío
        $sql = "UPDATE `Envios` e
                SET e.EstadoEnvio_estado_envio_id1 = ?
                WHERE e.envio_id = ?";

        $params = [$id_nuevo_estado, $id_envio];
        $types = "ii";

        // Si es un conductor, se añade la condición de que el envío le pertenezca
        if (strtolower($_SESSION['user_role']) === 'conductor') {
            $sql .= " AND e.Vehiculos_vehiculos_id IN (SELECT vehiculos_id FROM `Vehiculos` WHERE Conductores_conductor_id = ?)";
            $params[] = $id_conductor;
            $types .= "i";
        }

        $stmt_update = $conn->prepare($sql);

        if ($stmt_update === false) {
            throw new Exception("Error al preparar la consulta de actualización: " . $conn->error);
        }

        $stmt_update->bind_param($types, ...$params);

        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $_SESSION['update_status'] = "El estado del envío #$id_envio ha sido actualizado a '$nuevo_estado_desc'.";
            } else {
                $_SESSION['update_status'] = "No se pudo actualizar el estado. O el envío no le pertenece (si es conductor) o el estado ya era el mismo.";
            }
        } else {
            $_SESSION['update_status'] = "Error al ejecutar la actualización: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $_SESSION['update_status'] = "Error: Datos inválidos recibidos (estado o ID de envío incorrecto).";
    }
    $conn->close();
} else {
    $_SESSION['update_status'] = "Error: Método de solicitud no válido.";
}

// Redirigir a la página correcta según el rol
if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'gerente de ventas') {
    header("Location: ../area_personal_gerente.php");
} else {
    header("Location: ../area_personal_conductor.php");
}
exit();
