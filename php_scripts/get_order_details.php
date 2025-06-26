<?php
// Archivo: public/php_scripts/get_order_details.php
// Propósito: Devuelve los detalles de un envío específico en formato JSON.
// Accesible para roles autorizados (Gerente de Ventas, Conductor, Administrador).

session_start();

header('Content-Type: application/json');

// ===============================================
// === VERIFICACIÓN DE AUTORIZACIÓN ===
// ===============================================
$is_authorized = false;
$allowed_roles = ['administrador', 'conductor', 'gerente de ventas']; 

if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if (in_array(strtolower($_SESSION['user_role']), $allowed_roles)) { // <--- Problem likely here
        $is_authorized = true;
    }
}

if (!$is_authorized) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Acceso no autorizado para ver detalles del pedido.']);
    exit();
}
// ===============================================
// === FIN VERIFICACIÓN DE AUTORIZACIÓN ===
// ===============================================

require_once '../server/database.php'; 

$response = ['details' => null, 'error' => ''];

try {
    $envio_id = intval($_GET['envio_id'] ?? 0);

    if ($envio_id <= 0) {
        throw new Exception("ID de envío no especificado o inválido.");
    }

    // Consulta para obtener todos los detalles del envío, incluyendo cliente y vehículo
    $sql = "SELECT 
                e.envio_id, e.lugar_origen, e.lugar_distinto, e.fecha_envio, e.km,
                e.Vehiculos_vehiculos_id,
                es.estado_envio_id AS estado_actual_id,
                es.descripcion AS estado_actual,
                cl.id_cliente, 
                cl.nombre_cliente,
                cl.apellido_cliente,
                cl.telefono, 
                cl.numero_documento, 
                u.email AS cliente_email,
                v.tipo AS tipo_vehiculo, v.patente AS vehiculo_patente,
                de.id_detalle_envio, 
                de.peso_kg,
                de.alto_cm,
                de.largo_cm,
                de.ancho_cm,
                de.descripcion AS detalles_paquete_json,
                de.Servicios_servicio_id
            FROM `Envios` e
            JOIN `EstadoEnvio` es ON e.EstadoEnvio_estado_envio_id1 = es.estado_envio_id
            LEFT JOIN `Vehiculos` v ON e.Vehiculos_vehiculos_id = v.vehiculos_id
            LEFT JOIN `Clientes` cl ON e.Clientes_id_cliente = cl.id_cliente
            LEFT JOIN `Usuarios` u ON cl.Usuarios_id_usuario = u.id_usuario
            LEFT JOIN `DetalleEnvio` de ON e.envio_id = de.Envios_envio_id
            WHERE e.envio_id = ?";
    
    // Si el usuario es un conductor, añadir filtro para ver solo sus envíos
    if (strtolower($_SESSION['user_role']) === 'conductor') {
        $id_administrador = $_SESSION['user_id'];
        $stmt_conductor = $conn->prepare("SELECT conductor_id FROM `Conductores` WHERE Administradores_idAdministradores = ?");
        $stmt_conductor->bind_param("i", $id_administrador);
        $stmt_conductor->execute();
        $result_conductor = $stmt_conductor->get_result();
        if ($conductor_row = $result_conductor->fetch_assoc()) {
            $id_conductor = $conductor_row['conductor_id'];
            $sql .= " AND e.Vehiculos_vehiculos_id IN (SELECT vehiculos_id FROM `Vehiculos` WHERE Conductores_conductor_id = ?)";
            $bind_types = "ii";
            $bind_params = [$envio_id, $id_conductor];
        } else {
            throw new Exception("No se encontró el perfil de conductor asociado a su cuenta o no tiene vehículos.");
        }
    } else {
        $bind_types = "i";
        $bind_params = [$envio_id];
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error al preparar la consulta de detalles: " . $conn->error);
    }

    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($details = $result->fetch_assoc()) {
        $detalle_parsed = json_decode($details['detalles_paquete_json'], true);

        // --- INICIO DE LA SECCIÓN DE REVISIÓN PARA COSTO_ESTIMADO ---
        if (json_last_error() === JSON_ERROR_NONE && is_array($detalle_parsed)) {
            // Línea 103: Asegurarse de que el primer argumento de number_format sea un float.
            // Si $detalle_parsed['costo_estimado_final'] no existe o es null, usar 0.
            // floatval() convierte una cadena no numérica a 0.
            $costo_final_num = floatval($detalle_parsed['costo_estimado_final'] ?? 0);
            $details['costo_estimado'] = number_format($costo_final_num, 2, '.', ',');
            
            $details['descripcion_adicional_usuario'] = $detalle_parsed['descripcion_adicional_usuario'] ?? 'Sin descripción adicional.';
            
            // Revisa también estas líneas para asegurarte que no asignen 'N/A' a los campos numéricos si van a inputs type="number"
            // Se asume que estos ya vienen como números del SELECT SQL o se convierten a 0/null
            $details['peso_kg'] = $details['peso_kg'] !== null ? floatval($details['peso_kg']) : (isset($detalle_parsed['peso_kg']) ? floatval($detalle_parsed['peso_kg']) : 0);
            $details['largo_cm'] = $details['largo_cm'] !== null ? floatval($details['largo_cm']) : (isset($detalle_parsed['largo_cm']) ? floatval($detalle_parsed['largo_cm']) : 0);
            $details['ancho_cm'] = $details['ancho_cm'] !== null ? floatval($details['ancho_cm']) : (isset($detalle_parsed['ancho_cm']) ? floatval($detalle_parsed['ancho_cm']) : 0);
            $details['alto_cm'] = $details['alto_cm'] !== null ? floatval($details['alto_cm']) : (isset($detalle_parsed['alto_cm']) ? floatval($detalle_parsed['alto_cm']) : 0);

        } else {
            // Si el JSON es inválido o no existe, o no se pudo parsear
            $details['costo_estimado'] = 'N/A (Error JSON)'; // Aquí sí puedes usar 'N/A' porque es para display
            $details['descripcion_adicional_usuario'] = htmlspecialchars($details['detalles_paquete_json'] ?? 'Sin descripción adicional.');
            
            // Asegurarse de que los campos numéricos sean 0 si el JSON está mal
            $details['peso_kg'] = floatval($details['peso_kg'] ?? 0);
            $details['largo_cm'] = floatval($details['largo_cm'] ?? 0);
            $details['ancho_cm'] = floatval($details['ancho_cm'] ?? 0);
            $details['alto_cm'] = floatval($details['alto_cm'] ?? 0);
        }
        // --- FIN DE LA SECCIÓN DE REVISIÓN PARA COSTO_ESTIMADO ---
        
        $response['details'] = $details;

    } else {
        $response['error'] = 'Pedido no encontrado.';
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    // Log the full exception details, including file and line number
    error_log("FULL EXCEPTION in get_order_details.php: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile());

    // Send the full message back to the client for debugging purposes (TEMPORARY!)
    $response['error'] = 'Error en el servidor: ' . $e->getMessage() . ' (Línea: ' . $e->getLine() . ' en ' . basename($e->getFile()) . ')';
    
    // Si la conexión ya está abierta, no intentar cerrarla de nuevo en el catch si se va a salir
    // if ($conn && $conn->ping()) { $conn->close(); } // No es necesario si se hace un exit()
}

// Asegurarse de cerrar la conexión si el script no termina por un exit temprano o fatal error
if ($conn && $conn->ping()) { 
    $conn->close(); 
}
echo json_encode($response);
exit();