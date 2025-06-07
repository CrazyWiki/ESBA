<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'conductor') {
    $_SESSION['update_status'] = 'Error: Acceso no autorizado.';
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    require_once __DIR__ . '/../../server/database.php';

    $id_envio = $_POST['id_envio'] ?? 0;
    $nuevo_estado_desc = $_POST['nuevo_estado'] ?? '';
    $id_administrador = $_SESSION['user_id'];
    $id_conductor = null;

    $stmt_conductor = $conn->prepare("SELECT conductor_id FROM Conductores WHERE Administradores_idAdministradores = ?");
    $stmt_conductor->bind_param("i", $id_administrador);
    $stmt_conductor->execute();
    $result_conductor = $stmt_conductor->get_result();
    if ($conductor_row = $result_conductor->fetch_assoc()) {
        $id_conductor = $conductor_row['conductor_id'];
    }
    $stmt_conductor->close();

    if ($id_conductor === null) {
        $_SESSION['update_status'] = "Error: No se encontró el perfil de conductor.";
        header("Location: ../area_personal_conductor.php");
        exit();
    }
    
    // CORRECCIÓN: Ищем ID статуса с правильными именами столбцов
    $id_nuevo_estado = null;
    $stmt_estado = $conn->prepare("SELECT estado_envio_id FROM EstadoEnvio WHERE descripcion = ?");
    $stmt_estado->bind_param("s", $nuevo_estado_desc);
    $stmt_estado->execute();
    $result_estado = $stmt_estado->get_result();
    if($estado_row = $result_estado->fetch_assoc()) {
        $id_nuevo_estado = $estado_row['estado_envio_id'];
    }
    $stmt_estado->close();

    if ($id_envio > 0 && $id_nuevo_estado !== null) {
        
        // CORRECCIÓN FINAL: Запрос на обновление полностью синхронизирован с вашей схемой
        $sql = "UPDATE Envios e
                JOIN Vehiculos v ON e.Vehiculos_vehiculos_id = v.vehiculos_id
                SET e.EstadoEnvio_estado_envio_id1 = ?
                WHERE e.envio_id = ? AND v.Conductores_conductor_id = ?";
        
        $stmt_update = $conn->prepare($sql);
        $stmt_update->bind_param("iii", $id_nuevo_estado, $id_envio, $id_conductor);
        
        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $_SESSION['update_status'] = "El estado del envío #$id_envio ha sido actualizado a '$nuevo_estado_desc'.";
            } else {
                $_SESSION['update_status'] = "No se pudo actualizar el estado. O el envío no le pertenece o el estado ya era el mismo.";
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

header("Location: ../area_personal_conductor.php");
exit();
?>