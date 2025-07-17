<?php
session_start();

define('LARGA_DISTANCIA_KM_THRESHOLD', 450);
define('AVG_KM_PER_DAY', 800);

function get_valid_vehicle_type($service_name, $valid_types) {
    $service_name_lower = strtolower($service_name);
    foreach ($valid_types as $type) {
        if (str_contains($service_name_lower, strtolower($type))) {
            return $type;
        }
    }
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../server/database.php';

    $km_input = floatval($_POST['km'] ?? 0);
    $fecha_recogida = $_POST['fecha_envio'] ?? null;
    $selected_service_main_id = intval($_POST['selected_service_main_id'] ?? 0);
    $lugar_origen = trim($_POST['lugar_origen'] ?? '');
    $lugar_destino = trim($_POST['lugar_destino'] ?? '');
    $weight_input = floatval($_POST['weight'] ?? 0);
    $length_input = floatval($_POST['length'] ?? 0);
    $width_input = floatval($_POST['width'] ?? 0);
    $height_input = floatval($_POST['height'] ?? 0);
    $descripcion_paquete_usuario = trim($_POST['descripcion'] ?? '');
    $costo_estimado = floatval($_POST['costo_estimado'] ?? 0);

    $conn->begin_transaction();
    try {
        $id_usuario_actual = $_SESSION['user_id'];
        $stmt_cliente = $conn->prepare("SELECT id_cliente FROM Clientes WHERE Usuarios_id_usuario = ?");
        $stmt_cliente->bind_param("i", $id_usuario_actual);
        $stmt_cliente->execute();
        $id_cliente = $stmt_cliente->get_result()->fetch_assoc()['id_cliente'];
        $stmt_cliente->close();

        $result_types = $conn->query("SELECT DISTINCT tipo FROM Vehiculos");
        $valid_vehicle_types = [];
        while ($row = $result_types->fetch_assoc()) {
            $valid_vehicle_types[] = $row['tipo'];
        }
        $stmt_service_info = $conn->prepare("SELECT nombre_servicio FROM Servicios WHERE servicio_id = ?");
        $stmt_service_info->bind_param("i", $selected_service_main_id);
        $stmt_service_info->execute();
        $nombre_servicio_completo = $stmt_service_info->get_result()->fetch_assoc()['nombre_servicio'];
        $stmt_service_info->close();
        $tipo_vehiculo_requerido = get_valid_vehicle_type($nombre_servicio_completo, $valid_vehicle_types);

        if ($tipo_vehiculo_requerido === null) {
            throw new Exception("No se pudo determinar un tipo de vehículo válido para el servicio.");
        }
        
        $id_vehiculo_encontrado = null;
        
        $stmt_vehicles = $conn->prepare("SELECT vehiculos_id FROM Vehiculos WHERE tipo = ?");
        $stmt_vehicles->bind_param("s", $tipo_vehiculo_requerido);
        $stmt_vehicles->execute();
        $candidate_vehicles = $stmt_vehicles->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_vehicles->close();

        $stmt_all_trips = $conn->prepare("SELECT Vehiculos_vehiculos_id, fecha_envio, km FROM Envios");
        $stmt_all_trips->execute();
        $all_trips = $stmt_all_trips->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_all_trips->close();

        foreach ($candidate_vehicles as $vehicle) {
            $vehicle_id = $vehicle['vehiculos_id'];
            $is_vehicle_blocked = false;

            foreach ($all_trips as $trip) {
                if ($trip['Vehiculos_vehiculos_id'] == $vehicle_id && $trip['km'] >= LARGA_DISTANCIA_KM_THRESHOLD) {
                    $duracion_total = ceil($trip['km'] / AVG_KM_PER_DAY);
                    $fecha_inicio_viaje = new DateTime($trip['fecha_envio']);
                    $fecha_fin_viaje = (clone $fecha_inicio_viaje)->add(new DateInterval('P'.($duracion_total-1).'D'));
                    
                    if (new DateTime($fecha_recogida) >= $fecha_inicio_viaje && new DateTime($fecha_recogida) <= $fecha_fin_viaje) {
                        $is_vehicle_blocked = true;
                        break;
                    }
                }
            }

            if ($is_vehicle_blocked) continue;

            $total_km_on_selected_day = 0;
            foreach ($all_trips as $trip) {
                if ($trip['Vehiculos_vehiculos_id'] == $vehicle_id && $trip['fecha_envio'] == $fecha_recogida && $trip['km'] < LARGA_DISTANCIA_KM_THRESHOLD) {
                    $total_km_on_selected_day += $trip['km'];
                }
            }
            
            if ($km_input < LARGA_DISTANCIA_KM_THRESHOLD) {
                if (($total_km_on_selected_day + $km_input) <= AVG_KM_PER_DAY) {
                    $id_vehiculo_encontrado = $vehicle_id;
                    break;
                }
            } else {
                 if ($total_km_on_selected_day == 0) {
                     $id_vehiculo_encontrado = $vehicle_id;
                     break;
                 }
            }
        }
        
        if ($id_vehiculo_encontrado === null) {
            throw new Exception("Lo sentimos, no hay vehículos disponibles para la fecha y distancia solicitadas.");
        }

        $estado_result = $conn->query("SELECT estado_envio_id FROM EstadoEnvio WHERE descripcion = 'Pendiente' LIMIT 1");
        $id_estado_pendiente = $estado_result->fetch_assoc()['estado_envio_id'];

        $sql_envio = "INSERT INTO Envios (fecha_envio, lugar_origen, lugar_distinto, km, EstadoEnvio_estado_envio_id1, Vehiculos_vehiculos_id, Clientes_id_cliente) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_envio = $conn->prepare($sql_envio);
        $stmt_envio->bind_param("ssdsiii", $fecha_recogida, $lugar_origen, $lugar_destino, $km_input, $id_estado_pendiente, $id_vehiculo_encontrado, $id_cliente);
        $stmt_envio->execute();
        $envio_id = $conn->insert_id;
        $stmt_envio->close();
        
        $detalle_paquete_data = ['costo_estimado_final' => $costo_estimado, 'descripcion_adicional_usuario' => $descripcion_paquete_usuario];
        $detalle_paquete_json = json_encode($detalle_paquete_data);
        $cantidad = 1;
        $sql_detalle = "INSERT INTO DetalleEnvio (peso_kg, alto_cm, largo_cm, ancho_cm, descripcion, km, cantidad, Envios_envio_id, Servicios_servicio_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_detalle = $conn->prepare($sql_detalle);
        $stmt_detalle->bind_param("ddddsdiii", $weight_input, $height_input, $length_input, $width_input, $detalle_paquete_json, $km_input, $cantidad, $envio_id, $selected_service_main_id);
        $stmt_detalle->execute();
        $stmt_detalle->close();

        $conn->commit();
        $_SESSION['order_status_msg'] = "¡Tu pedido ha sido creado exitosamente! ID de envío: " . $envio_id;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['order_status_msg'] = "Error al crear el pedido: " . $e->getMessage();
    }
    $conn->close();
} else {
    $_SESSION['order_status_msg'] = "Error: Método de solicitud no válido.";
}

header("Location: ../area_personal_cliente.php");
exit();
?>