<?php
session_start();


if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'cliente')) {
    $_SESSION['order_status_msg'] = 'Error: Acceso no autorizado o sesión expirada.';
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once '../server/database.php';

    $lugar_origen = trim($_POST['lugar_origen'] ?? '');
    $lugar_destino = trim($_POST['lugar_destino'] ?? '');
    $km_input = floatval($_POST['km'] ?? 0);
    $weight_input = floatval($_POST['weight'] ?? 0);
    $length_input = floatval($_POST['length'] ?? 0);
    $width_input = floatval($_POST['width'] ?? 0);
    $height_input = floatval($_POST['height'] ?? 0);
    $descripcion_paquete_usuario = trim($_POST['descripcion'] ?? ''); 
    $fecha_recogida = $_POST['fecha_envio'] ?? null;

    $costo_estimado = floatval($_POST['costo_estimado'] ?? 0);
    $selected_service_main_id = intval($_POST['selected_service_main_id'] ?? 0);

    $_SESSION['form_data_on_error'] = $_POST;
    $_SESSION['form_data_on_error']['fecha_envio_min'] = date('Y-m-d'); 

    $id_usuario_actual = $_SESSION['user_id'];
    $id_cliente = null;

    $conn->begin_transaction();

    try {
        $stmt_cliente = $conn->prepare("SELECT id_cliente FROM Clientes WHERE Usuarios_id_usuario = ?");
        $stmt_cliente->bind_param("i", $id_usuario_actual);
        $stmt_cliente->execute();
        $result_cliente = $stmt_cliente->get_result();
        if ($cliente_row = $result_cliente->fetch_assoc()) {
            $id_cliente = $cliente_row['id_cliente'];
        }
        $stmt_cliente->close();

        if ($id_cliente === null) {
            throw new Exception("Error: No se encontró un perfil de cliente asociado a su cuenta para crear el pedido.");
        }
       
        $tipo_servicio_para_vehiculo = '';
        $stmt_service_info = $conn->prepare("SELECT nombre_servicio FROM Servicios WHERE servicio_id = ?");
        $stmt_service_info->bind_param("i", $selected_service_main_id);
        $stmt_service_info->execute();
        $result_service_info = $stmt_service_info->get_result();
        $service_info_row = $result_service_info->fetch_assoc();
        $stmt_service_info->close();

        if ($service_info_row) {
            $nombre_servicio_completo = $service_info_row['nombre_servicio'];
            $commonServiceName = trim(strtolower(preg_replace('/\s*\((?:Por (?:KM|KG|Hora)|Base)\)\s*/i', '', $nombre_servicio_completo)));

            if (str_contains($commonServiceName, 'motocicleta')) {
                $tipo_servicio_para_vehiculo = 'motocicleta';
            } elseif (str_contains($commonServiceName, 'furgoneta') || str_contains($commonServiceName, 'furgón')) {
                $tipo_servicio_para_vehiculo = 'furgón';
            } elseif (str_contains($commonServiceName, 'camión') || str_contains($commonServiceName, 'camion')) {
                $tipo_servicio_para_vehiculo = 'camión mediano';
            } elseif (str_contains($commonServiceName, 'pickup')) {
                $tipo_servicio_para_vehiculo = 'pickup';
            }
        }

        $estado_result = $conn->query("SELECT estado_envio_id FROM EstadoEnvio WHERE descripcion = 'Pendiente' LIMIT 1");
        $id_estado_pendiente = null;
        if ($estado_row = $estado_result->fetch_assoc()) {
            $id_estado_pendiente = $estado_row['estado_envio_id'];
        } else {
            throw new Exception("Error: No se encontró el estado 'Pendiente'.");
        }

        if (empty($lugar_origen) || empty($lugar_destino) || $km_input <= 0 || empty($fecha_recogida) || $selected_service_main_id <= 0 || $costo_estimado <= 0) {
            throw new Exception("Error: Por favor, complete todos los campos obligatorios del formulario de envío (Origen, Destino, KM, Fecha, Tipo de Envío, Costo Estimado).");
        }
        

        $id_vehiculo = null;
        $MAX_KM_POR_DIA = 500; 

        $candidatos_vehiculos_ids = [];
        $sql_get_typed_vehiculos = $conn->prepare("SELECT vehiculos_id FROM Vehiculos WHERE LOWER(tipo) = ?");
        $sql_get_typed_vehiculos->bind_param("s", $tipo_servicio_para_vehiculo);
        $sql_get_typed_vehiculos->execute();
        $result_typed_vehiculos = $sql_get_typed_vehiculos->get_result();
        
        if ($result_typed_vehiculos->num_rows === 0) {
            throw new Exception("No hay vehículos del tipo '{$tipo_servicio_para_vehiculo}' registrados para este servicio.");
        }
        
        while ($row_vehiculo = $result_typed_vehiculos->fetch_assoc()) {
            $candidatos_vehiculos_ids[] = $row_vehiculo['vehiculos_id'];
        }
        $sql_get_typed_vehiculos->close();
        
        $id_vehiculo_encontrado = null;
        foreach ($candidatos_vehiculos_ids as $vehiculo_candidato_id) {
            $stmt_sum_km = $conn->prepare("SELECT SUM(km) AS total_km_for_day FROM Envios WHERE Vehiculos_vehiculos_id = ? AND fecha_envio = ?");
            $stmt_sum_km->bind_param("is", $vehiculo_candidato_id, $fecha_recogida);
            $stmt_sum_km->execute();
            $result_sum_km = $stmt_sum_km->get_result();
            $row_sum_km = $result_sum_km->fetch_assoc();
            $stmt_sum_km->close();

            $total_km_for_day = $row_sum_km['total_km_for_day'] ?: 0;
            
            if (($total_km_for_day + $km_input) <= $MAX_KM_POR_DIA) {
                $id_vehiculo_encontrado = $vehiculo_candidato_id;
                break;
            }
        }

        if ($id_vehiculo_encontrado === null) {
            throw new Exception("Lo sentimos, no hay vehículos disponibles del tipo '{$tipo_servicio_para_vehiculo}' para la fecha '{$fecha_recogida}' con la distancia solicitada. Por favor, pruebe otra fecha o tipo de envío.");
        }
        $id_vehiculo = $id_vehiculo_encontrado;
      
        $sql_envio = "INSERT INTO Envios (
            fecha_envio, 
            lugar_origen, 
            lugar_distinto, 
            km, 
            EstadoEnvio_estado_envio_id1, 
            Vehiculos_vehiculos_id, 
            Clientes_id_cliente
        ) VALUES (?, ?, ?, ?, ?, ?, ?)"; 

        $stmt_envio = $conn->prepare($sql_envio);
        if ($stmt_envio === false) {
            throw new Exception("Error al preparar la consulta de Envios: " . $conn->error);
        }

        $stmt_envio->bind_param("ssdsiii",
            $fecha_recogida,
            $lugar_origen,
            $lugar_destino,
            $km_input, 
            $id_estado_pendiente,
            $id_vehiculo,
            $id_cliente
        );
        $stmt_envio->execute();
        $envio_id = $conn->insert_id; 
        $stmt_envio->close();

        $detalle_paquete_data = [
            'costo_estimado_final' => $costo_estimado,
            'id_servicio_calculadora' => $selected_service_main_id,
            'descripcion_adicional_usuario' => $descripcion_paquete_usuario,
            'peso_kg' => $weight_input,
            'largo_cm' => $length_input,
            'ancho_cm' => $width_input,
            'alto_cm' => $height_input,
        ];
        $detalle_paquete_json = json_encode($detalle_paquete_data);

        $sql_detalle = "INSERT INTO DetalleEnvio (
            peso_kg, 
            alto_cm, 
            largo_cm, 
            ancho_cm, 
            descripcion, 
            km, 
            cantidad, 
            Envios_envio_id, 
            Servicios_servicio_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_detalle = $conn->prepare($sql_detalle);
        if ($stmt_detalle === false) {
            throw new Exception("Error al preparar la consulta de DetalleEnvio: " . $conn->error);
        }

        $cantidad = 1; 
        $stmt_detalle->bind_param("ddddsdiii",
            $weight_input,
            $height_input,
            $length_input,
            $width_input,
            $detalle_paquete_json, 
            $km_input,
            $cantidad,
            $envio_id,
            $selected_service_main_id
        );
        $stmt_detalle->execute();
        $stmt_detalle->close();

        $conn->commit();
        $_SESSION['order_status_msg'] = "¡Tu pedido ha sido creado exitosamente! ID de envío: " . $envio_id;
        $_SESSION['clear_calculator_data_flag'] = true; 
        
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