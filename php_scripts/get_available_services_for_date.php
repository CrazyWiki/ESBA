<?php
header('Content-Type: application/json');
require_once '../server/database.php';

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

$fecha_seleccionada = $_GET['fecha'] ?? null;
$km_a_verificar = floatval($_GET['km'] ?? 0);

if (!$fecha_seleccionada || $km_a_verificar <= 0) {
    echo json_encode(['available_services_ids' => []]);
    exit();
}

try {
    $result_types = $conn->query("SELECT DISTINCT tipo FROM Vehiculos");
    $valid_vehicle_types = [];
    while ($row = $result_types->fetch_assoc()) {
        $valid_vehicle_types[] = $row['tipo'];
    }

    $result_services = $conn->query("SELECT servicio_id, nombre_servicio FROM Servicios WHERE unidad_medida_tarifa = 'base' OR unidad_medida_tarifa IS NULL");
    $services = [];
    while ($row = $result_services->fetch_assoc()) {
        $services[] = $row;
    }

    $stmt_all_trips = $conn->prepare("SELECT Vehiculos_vehiculos_id, fecha_envio, km FROM Envios WHERE fecha_envio <= ? ORDER BY fecha_envio DESC");
    $stmt_all_trips->bind_param("s", $fecha_seleccionada);
    $stmt_all_trips->execute();
    $all_trips = $stmt_all_trips->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_all_trips->close();

    $servicios_disponibles_ids = [];

    foreach ($services as $service) {
        $required_vehicle_type = get_valid_vehicle_type($service['nombre_servicio'], $valid_vehicle_types);
        if (!$required_vehicle_type) continue;

        $stmt_vehicles = $conn->prepare("SELECT vehiculos_id FROM Vehiculos WHERE tipo = ?");
        $stmt_vehicles->bind_param("s", $required_vehicle_type);
        $stmt_vehicles->execute();
        $candidate_vehicles = $stmt_vehicles->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_vehicles->close();

        $is_service_available = false;

        foreach ($candidate_vehicles as $vehicle) {
            $vehicle_id = $vehicle['vehiculos_id'];
            $is_vehicle_blocked = false;

            foreach ($all_trips as $trip) {
                if ($trip['Vehiculos_vehiculos_id'] == $vehicle_id && $trip['km'] >= LARGA_DISTANCIA_KM_THRESHOLD) {
                    $duracion_total = ceil($trip['km'] / AVG_KM_PER_DAY);
                    $fecha_inicio_viaje = new DateTime($trip['fecha_envio']);
                    $fecha_fin_viaje = (clone $fecha_inicio_viaje)->add(new DateInterval('P' . ($duracion_total - 1) . 'D'));
                    
                    if (new DateTime($fecha_seleccionada) >= $fecha_inicio_viaje && new DateTime($fecha_seleccionada) <= $fecha_fin_viaje) {
                        $is_vehicle_blocked = true;
                        break;
                    }
                }
            }

            if ($is_vehicle_blocked) {
                continue;
            }

            $total_km_on_selected_day = 0;
            foreach ($all_trips as $trip) {
                if ($trip['Vehiculos_vehiculos_id'] == $vehicle_id && $trip['fecha_envio'] == $fecha_seleccionada) {
                    if ($trip['km'] < LARGA_DISTANCIA_KM_THRESHOLD) {
                        $total_km_on_selected_day += $trip['km'];
                    }
                }
            }
            
            if ($km_a_verificar < LARGA_DISTANCIA_KM_THRESHOLD) {
                if (($total_km_on_selected_day + $km_a_verificar) <= AVG_KM_PER_DAY) {
                    $is_service_available = true;
                    break;
                }
            } else {
                 if ($total_km_on_selected_day == 0) {
                     $is_service_available = true;
                     break;
                 }
            }
        }

        if ($is_service_available) {
            $servicios_disponibles_ids[] = (int)$service['servicio_id'];
        }
    }

    echo json_encode(['available_services_ids' => $servicios_disponibles_ids]);

} catch (Exception $e) {
    error_log("Error en get_available_services_for_date.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['available_services_ids' => [], 'error' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?>