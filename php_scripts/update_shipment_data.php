<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || !isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'gerente de ventas') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}

require_once '../server/database.php';
$response = ['success' => false, 'message' => 'Error desconocido.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit();
}

$conn->begin_transaction();

try {
    $envio_id = filter_input(INPUT_POST, 'envio_id', FILTER_VALIDATE_INT);
    $id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
    $id_detalle_envio = filter_input(INPUT_POST, 'id_detalle_envio', FILTER_VALIDATE_INT);

    if (!$envio_id || !$id_cliente || !$id_detalle_envio) {
        throw new Exception("Datos de ID insuficientes.");
    }

    $stmt_envio = $conn->prepare("UPDATE Envios SET fecha_envio = ?, lugar_origen = ?, lugar_distinto = ?, km = ?, Vehiculos_vehiculos_id = ?, EstadoEnvio_estado_envio_id1 = ? WHERE envio_id = ?");
    $stmt_envio->bind_param("sssdiii", $_POST['fecha_envio'], $_POST['lugar_origen'], $_POST['lugar_distinto'], $_POST['km'], $_POST['Vehiculos_vehiculos_id'], $_POST['EstadoEnvio_estado_envio_id1'], $envio_id);
    $stmt_envio->execute();
    $stmt_envio->close();
    
    $stmt_cliente = $conn->prepare("UPDATE Clientes SET nombre_cliente = ?, apellido_cliente = ?, numero_documento = ?, telefono = ? WHERE id_cliente = ?");
    $stmt_cliente->bind_param("ssssi", $_POST['nombre_cliente'], $_POST['apellido_cliente'], $_POST['numero_documento'], $_POST['telefono'], $id_cliente);
    $stmt_cliente->execute();
    $stmt_cliente->close();
    
    $stmt_get_json = $conn->prepare("SELECT descripcion FROM DetalleEnvio WHERE id_detalle_envio = ?");
    $stmt_get_json->bind_param("i", $id_detalle_envio);
    $stmt_get_json->execute();
    $current_json_str = $stmt_get_json->get_result()->fetch_assoc()['descripcion'];
    $stmt_get_json->close();

    $detalles_array = json_decode($current_json_str, true) ?: [];

    // --- ДОБАВЛЕНА ЛОГИКА ОБНОВЛЕНИЯ ЦЕНЫ ---
    if (isset($_POST['costo_final_corregido'])) {
        $detalles_array['costo_estimado_final'] = filter_input(INPUT_POST, 'costo_final_corregido', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    // --- КОНЕЦ НОВОЙ ЛОГИКИ ---

    $detalles_array['descripcion_adicional_usuario'] = trim($_POST['descripcion_adicional_usuario']);
    $new_json_str = json_encode($detalles_array);

    $stmt_detalle = $conn->prepare("UPDATE DetalleEnvio SET peso_kg = ?, alto_cm = ?, largo_cm = ?, ancho_cm = ?, descripcion = ? WHERE id_detalle_envio = ?");
    $stmt_detalle->bind_param("ddddsi", $_POST['peso_kg'], $_POST['alto_cm'], $_POST['largo_cm'], $_POST['ancho_cm'], $new_json_str, $id_detalle_envio);
    $stmt_detalle->execute();
    $stmt_detalle->close();

    $conn->commit();
    $response['success'] = true;
    $response['message'] = "Pedido #" . $envio_id . " actualizado correctamente.";

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    $response['message'] = "Error al actualizar el pedido: " . $e->getMessage();
}

if (isset($conn)) {
    $conn->close();
}
echo json_encode($response);
?>