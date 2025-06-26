<?php

session_start(); 
require_once '../server/database.php';

header('Content-Type: application/json');

$response = ['available_services_ids' => [], 'message' => ''];

try {
    $fecha_recogida = $_GET['fecha'] ?? '';
    $km_input = floatval($_GET['km'] ?? 0);

    if (empty($fecha_recogida) || $km_input <= 0) {
        throw new Exception("Faltan parámetros 'fecha' o 'km' necesarios para la verificación.");
    }

    $MAX_KM_POR_DIA = 500;

    $vehiculos_por_tipo = [];
    $sqlVehiculos = "SELECT vehiculos_id, tipo FROM Vehiculos";
    $resultVehiculos = $conn->query($sqlVehiculos);
    if ($resultVehiculos === false) {
        throw new Exception("Error al obtener tipos de vehículos: " . $conn->error);
    }
    while ($rowVehiculo = $resultVehiculos->fetch_assoc()) {
        $vehiculos_por_tipo[strtolower($rowVehiculo['tipo'])][] = $rowVehiculo['vehiculos_id'];
    }

    $vehiculos_carga_km_hoy = [];
    $stmt_cargas = $conn->prepare("SELECT Vehiculos_vehiculos_id, SUM(km) AS total_km FROM Envios WHERE fecha_envio = ? GROUP BY Vehiculos_vehiculos_id");
    $stmt_cargas->bind_param("s", $fecha_recogida);
    $stmt_cargas->execute();
    $result_cargas = $stmt_cargas->get_result();
    while ($row_carga = $result_cargas->fetch_assoc()) {
        $vehiculos_carga_km_hoy[$row_carga['Vehiculos_vehiculos_id']] = $row_carga['total_km'];
    }
    $stmt_cargas->close();

    $available_service_ids_for_date = [];
    $raw_all_services_data = [];

    // Recargar la lógica de agrupamiento de servicios para obtener el mapeo de service_id a tipo de vehiculo
    $sql_all_services_data_no_join = "
        SELECT
            s.servicio_id,
            s.nombre_servicio,
            s.unidad_medida_tarifa,
            s.descripcion
        FROM
            Servicios s
        ORDER BY
            s.nombre_servicio, s.unidad_medida_tarifa
    ";
    $result_all_services_data = $conn->query($sql_all_services_data_no_join);

    if ($result_all_services_data === false) {
        throw new Exception("Error al obtener todos los servicios para disponibilidad: " . $conn->error);
    }

    while ($row = $result_all_services_data->fetch_assoc()) {
        $raw_all_services_data[] = $row;
    }

    $service_type_mapping = [];
    $vehiculosCapacidades = []; // Necesario para el mapeo de tipo de vehiculo
    $sqlCapacidades = "SELECT tipo, capacidad_kg, capacidad_m3 FROM Vehiculos";
    $resCapacidades = $conn->query($sqlCapacidades);
    while($rowCap = $resCapacidades->fetch_assoc()) {
        $vehiculosCapacidades[strtolower($rowCap['tipo'])] = ['max_peso_kg' => floatval($rowCap['capacidad_kg']), 'max_volumen_m3' => floatval($rowCap['capacidad_m3'])];
    }


    foreach ($raw_all_services_data as $row) {
        $commonServiceName = trim(strtolower(preg_replace('/\s*\((?:Por (?:KM|KG|Hora)|Base)\)\s*/i', '', $row['nombre_servicio'])));
        
        $service_type = '';
        if (str_contains($commonServiceName, 'motocicleta')) { $service_type = 'motocicleta'; } 
        elseif (str_contains($commonServiceName, 'furgoneta') || str_contains($commonServiceName, 'furgón')) { $service_type = 'furgón'; } 
        elseif (str_contains($commonServiceName, 'camión') || str_contains($commonServiceName, 'camion')) { $service_type = 'camión mediano'; } 
        elseif (str_contains($commonServiceName, 'pickup')) { $service_type = 'pickup'; }

        // Si el servicio no tiene un tipo de vehículo asociado, lo saltamos
        if (empty($service_type) || !isset($vehiculos_por_tipo[$service_type])) {
            continue; 
        }

        $vehiculos_ids_de_este_tipo = $vehiculos_por_tipo[$service_type];
        
        $found_available_for_this_service = false;
        foreach ($vehiculos_ids_de_este_tipo as $vehiculo_id) {
            $current_km_assigned = $vehiculos_carga_km_hoy[$vehiculo_id] ?? 0;
            
            if (($current_km_assigned + $km_input) <= $MAX_KM_POR_DIA) {
                $found_available_for_this_service = true;
                break;
            }
        }

        if ($found_available_for_this_service) {
            $response['available_services_ids'][] = $row['servicio_id'];
        }
    }

    if (empty($response['available_services_ids'])) {
        $response['message'] = "No hay servicios disponibles para la fecha y distancia solicitada.";
    } else {
        $response['message'] = "Servicios disponibles para esta fecha.";
    }

} catch (Exception $e) {
    $response['available_services_ids'] = [];
    $response['message'] = 'Error en el servidor al verificar disponibilidad: ' . $e->getMessage();
    error_log("Error en get_available_services_for_date.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>