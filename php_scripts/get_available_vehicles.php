<?php
// public/php_scripts/get_available_vehicles.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

require_once '../server/database.php';

define('LARGA_DISTANCIA_KM_THRESHOLD', 450);
define('AVG_KM_PER_DAY', 800);

$fecha_seleccionada = $_GET['fecha'] ?? null;
$km_a_verificar = floatval($_GET['km'] ?? 0);
$current_envio_id = filter_input(INPUT_GET, 'current_envio_id', FILTER_VALIDATE_INT);

if (!$fecha_seleccionada || $km_a_verificar <= 0) {
    echo json_encode([]);
    exit();
}

try {
    $stmt_all_trips = $conn->prepare("SELECT Vehiculos_vehiculos_id, fecha_envio, km FROM Envios WHERE envio_id != ?");
    $stmt_all_trips->bind_param("i", $current_envio_id);
    $stmt_all_trips->execute();
    $other_trips = $stmt_all_trips->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_all_trips->close();

    $stmt_vehicles = $conn->prepare("SELECT vehiculos_id, tipo, patente FROM Vehiculos ORDER BY tipo, patente");
    $stmt_vehicles->execute();
    $all_vehicles = $stmt_vehicles->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_vehicles->close();

    $available_vehicles = [];

    foreach ($all_vehicles as $vehicle) {
        $vehicle_id = $vehicle['vehiculos_id'];
        $is_vehicle_blocked = false;

        foreach ($other_trips as $trip) {
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
        foreach ($other_trips as $trip) {
            if ($trip['Vehiculos_vehiculos_id'] == $vehicle_id && $trip['fecha_envio'] == $fecha_seleccionada && $trip['km'] < LARGA_DISTANCIA_KM_THRESHOLD) {
                $total_km_on_selected_day += $trip['km'];
            }
        }
        
        $is_this_vehicle_available = false;
        if ($km_a_verificar < LARGA_DISTANCIA_KM_THRESHOLD) {
            if (($total_km_on_selected_day + $km_a_verificar) <= AVG_KM_PER_DAY) {
                $is_this_vehicle_available = true;
            }
        } else {
             if ($total_km_on_selected_day == 0) {
                 $is_this_vehicle_available = true;
             }
        }

        if ($is_this_vehicle_available) {
            $available_vehicles[] = [
                'id' => $vehicle['vehiculos_id'],
                'display_text' => "{$vehicle['tipo']} ({$vehicle['patente']})"
            ];
        }
    }

    echo json_encode($available_vehicles);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor al obtener vehículos: ' . $e->getMessage()]);
}

$conn->close();
?>