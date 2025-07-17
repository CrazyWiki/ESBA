<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../server/database.php';

$response = ['success' => false, 'message' => 'Error no especificado.'];

// Проверка авторизации теперь использует правильное имя переменной: 'role'
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'cliente') {
    $response['message'] = 'Error de autorización: No ha iniciado sesión como cliente.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Error: Método no válido.';
    http_response_code(405);
    echo json_encode($response);
    exit();
}

$conn->begin_transaction();
try {
    $input = json_decode(file_get_contents('php://input'), true);
    $envio_id = (int)($input['envio_id'] ?? 0);
    $user_id = (int)$_SESSION['user_id'];

    if ($envio_id <= 0) {
        throw new Exception("ID de pedido no válido.");
    }

    $stmt_cliente = $conn->prepare("SELECT id_cliente FROM Clientes WHERE Usuarios_id_usuario = ?");
    $stmt_cliente->bind_param("i", $user_id);
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();
    $cliente_row = $result_cliente->fetch_assoc();
    $stmt_cliente->close();

    if (!$cliente_row) {
        throw new Exception("Perfil de cliente no encontrado para este usuario.");
    }
    $id_cliente = (int)$cliente_row['id_cliente'];

    $stmt_check = $conn->prepare("
        SELECT e.envio_id 
        FROM Envios e
        JOIN EstadoEnvio es ON e.EstadoEnvio_estado_envio_id1 = es.estado_envio_id
        WHERE e.envio_id = ? AND e.Clientes_id_cliente = ? AND es.descripcion = 'Pendiente'
    ");
    $stmt_check->bind_param("ii", $envio_id, $id_cliente);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        $stmt_check->close();
        throw new Exception("El pedido no existe, no le pertenece o su estado no permite la eliminación.");
    }
    $stmt_check->close();

    $stmt_delete_detalle = $conn->prepare("DELETE FROM DetalleEnvio WHERE Envios_envio_id = ?");
    $stmt_delete_detalle->bind_param("i", $envio_id);
    $stmt_delete_detalle->execute();
    $stmt_delete_detalle->close();

    $stmt_delete_envio = $conn->prepare("DELETE FROM Envios WHERE envio_id = ?");
    $stmt_delete_envio->bind_param("i", $envio_id);
    $stmt_delete_envio->execute();

    if ($stmt_delete_envio->affected_rows > 0) {
        $conn->commit(); 
        $response['success'] = true;
        $response['message'] = "El pedido #$envio_id ha sido eliminado correctamente.";
    } else {
        throw new Exception("La operación de eliminación no afectó a ninguna fila. El pedido podría haber sido eliminado previamente.");
    }
    $stmt_delete_envio->close();

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500); 
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

echo json_encode($response);
exit();