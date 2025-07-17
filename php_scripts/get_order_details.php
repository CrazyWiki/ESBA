<?php
// Archivo: public/php_scripts/get_order_details.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit();
}

require_once '../server/database.php';

$envio_id = filter_input(INPUT_GET, 'envio_id', FILTER_VALIDATE_INT);
if (!$envio_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de envío no válido.']);
    exit();
}

try {
    $sql = "SELECT e.envio_id, e.lugar_origen, e.lugar_distinto, e.fecha_envio, e.km, e.Vehiculos_vehiculos_id, es.estado_envio_id AS estado_actual_id, c.id_cliente, c.nombre_cliente, c.apellido_cliente, c.numero_documento, c.telefono, u.email AS cliente_email, de.id_detalle_envio, de.peso_kg, de.alto_cm, de.largo_cm, de.ancho_cm, de.descripcion AS detalles_paquete_json FROM Envios e JOIN EstadoEnvio es ON e.EstadoEnvio_estado_envio_id1 = es.estado_envio_id LEFT JOIN Clientes c ON e.Clientes_id_cliente = c.id_cliente LEFT JOIN Usuarios u ON c.Usuarios_id_usuario = u.id_usuario LEFT JOIN DetalleEnvio de ON e.envio_id = de.Envios_envio_id WHERE e.envio_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $envio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();
    $stmt->close();

    if ($details) {
        // Инициализируем переменные со значениями по умолчанию
        $details['costo_final_corregido'] = 0.00;
        $details['descripcion_adicional_usuario'] = '';

        // Безопасно извлекаем данные из JSON
        if (!empty($details['detalles_paquete_json'])) {
            $detalles_parsed = json_decode($details['detalles_paquete_json'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($detalles_parsed)) {
                // Если ключ существует, используем его значение, иначе оставляем значение по умолчанию
                if (isset($detalles_parsed['costo_estimado_final'])) {
                    $details['costo_final_corregido'] = $detalles_parsed['costo_estimado_final'];
                }
                if (isset($detalles_parsed['descripcion_adicional_usuario'])) {
                    $details['descripcion_adicional_usuario'] = $detalles_parsed['descripcion_adicional_usuario'];
                }
            }
        }
        
        unset($details['detalles_paquete_json']); // Удаляем исходное JSON-поле из ответа
        echo json_encode(['details' => $details]);

    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No se encontraron detalles para el pedido solicitado.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor al obtener detalles: ' . $e->getMessage()]);
}

$conn->close();
?>