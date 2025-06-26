<?php
// get_calculation_data.php
// Este script recibe parámetros de la calculadora del cliente y devuelve los servicios disponibles
require_once '../server/database.php';

header('Content-Type: application/json');

$response = ['error' => ''];
$calculationData = [];

try {
    // --- 1. Recibir parámetros de la calculadora del cliente ---
    $km_input = floatval($_GET['km'] ?? 0);
    $weight_input = floatval($_GET['weight'] ?? 0);
    $length_input = floatval($_GET['length'] ?? 0);
    $width_input = floatval($_GET['width'] ?? 0);
    $height_input = floatval($_GET['height'] ?? 0);

    // Calcular volumen en m3 desde cm
    $volumeM3_input = ($length_input * $width_input * $height_input) / 1000000;

    // --- 2. Obtener capacidades de los vehículos disponibles ---
    $vehiculosCapacidades = [];
    $sqlVehiculos = "SELECT tipo, capacidad_kg, capacidad_m3 FROM Vehiculos";
    $resultVehiculos = $conn->query($sqlVehiculos);

    if ($resultVehiculos === false) {
        throw new Exception("Error al obtener capacidades de vehículos: " . $conn->error);
    }

    while ($rowVehiculo = $resultVehiculos->fetch_assoc()) {
        $vehiculosCapacidades[strtolower($rowVehiculo['tipo'])] = [
            'max_peso_kg' => floatval($rowVehiculo['capacidad_kg']),
            'max_volumen_m3' => floatval($rowVehiculo['capacidad_m3'])
        ];
    }

    // --- 3. Consulta principal para servicios y tarifas, AHORA CON FILTRADO POR CAPACIDAD ---
    $sql = "
        SELECT
            s.servicio_id,
            s.nombre_servicio,
            s.descripcion,
            s.unidad_medida_tarifa,
            t.tarifa_id,
            t.precio,
            t.factor_multiplicador
        FROM
            Servicios s
        LEFT JOIN
            Tarifas t ON s.servicio_id = t.Servicios_servicio_id
        WHERE
            t.fecha_vigencia_inicio <= CURDATE() AND (t.fecha_vigencia_fin IS NULL OR t.fecha_vigencia_fin >= CURDATE())
        ORDER BY
            s.nombre_servicio, s.unidad_medida_tarifa
    ";

    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Error al ejecutar la consulta de servicios: " . $conn->error);
    }

    $rawServicesData = [];
    while ($row = $result->fetch_assoc()) {
        $rawServicesData[] = $row;
    }

    // 4. Agrupar servicios lógicos, asignar capacidades y aplicar FILTRO DE CAPACIDAD EN PHP
    $groupedLogicalServices = [];
    foreach ($rawServicesData as $row) {
        $commonServiceName = preg_replace('/\s*\((?:Por (?:KM|KG|Hora)|Base)\)\s*/i', '', $row['nombre_servicio']);
        $commonServiceName = trim($commonServiceName);
        $commonServiceKey = strtolower($commonServiceName);

        // Inicializar el servicio lógico si es la primera vez que lo vemos
        if (!isset($groupedLogicalServices[$commonServiceKey])) {
            $groupedLogicalServices[$commonServiceKey] = [
                'main_servicio_id' => null,
                'nombre_servicio' => $commonServiceName,
                'descripcion' => $row['descripcion'],
                'capacidades' => [
                    'max_peso_kg' => null, 
                    'max_volumen_m3' => null 
                ],
                'costo_base' => 0,
                'costo_por_km' => 0,
                'costo_por_kg' => 0,
                'icon_class' => 'fas fa-box' 
            ];

            if (strtolower($row['unidad_medida_tarifa']) === 'base') {
                 $groupedLogicalServices[$commonServiceKey]['main_servicio_id'] = $row['servicio_id'];
            } else {
                 if ($groupedLogicalServices[$commonServiceKey]['main_servicio_id'] === null) {
                     $groupedLogicalServices[$commonServiceKey]['main_servicio_id'] = $row['servicio_id'];
                 }
            }

            $currentServiceType = '';
            if (str_contains($commonServiceKey, 'motocicleta')) {
                $currentServiceType = 'motocicleta';
                $groupedLogicalServices[$commonServiceKey]['icon_class'] = 'fas fa-motorcycle';
            } elseif (str_contains($commonServiceKey, 'furgoneta') || str_contains($commonServiceKey, 'furgón')) {
                $currentServiceType = 'furgoneta';
                $groupedLogicalServices[$commonServiceKey]['icon_class'] = 'fas fa-truck-pickup';
            } elseif (str_contains($commonServiceKey, 'camión') || str_contains($commonServiceKey, 'camion')) {
                $currentServiceType = 'camión';
                $groupedLogicalServices[$commonServiceKey]['icon_class'] = 'fas fa-truck';
            } elseif (str_contains($commonServiceKey, 'pickup')) {
                $currentServiceType = 'pickup';
                $groupedLogicalServices[$commonServiceKey]['icon_class'] = 'fas fa-truck-pickup';
            }

            if (!empty($currentServiceType) && isset($vehiculosCapacidades[$currentServiceType])) {
                $groupedLogicalServices[$commonServiceKey]['capacidades'] = $vehiculosCapacidades[$currentServiceType];
            }
        }

        $costo_calculado = floatval($row['precio'] ?: 0) * floatval($row['factor_multiplicador'] ?: 1);
        $unidad = strtolower($row['unidad_medida_tarifa']);

        if ($unidad === 'base') {
            $groupedLogicalServices[$commonServiceKey]['costo_base'] = $costo_calculado;
        } elseif ($unidad === 'km') {
            $groupedLogicalServices[$commonServiceKey]['costo_por_km'] = $costo_calculado;
        } elseif ($unidad === 'kg') {
            $groupedLogicalServices[$commonServiceKey]['costo_por_kg'] = $costo_calculado;
        }
    }

    // --- 5. Filtrar servicios por peso y volumen AQUÍ EN PHP ---
    $finalFilteredServices = [];
    foreach ($groupedLogicalServices as $service) {
        $maxPeso = $service['capacidades']['max_peso_kg'];
        $maxVolumen = $service['capacidades']['max_volumen_m3'];

        $pesoCumple = ($maxPeso === null || $weight_input <= $maxPeso);
        $volumenCumple = ($maxVolumen === null || $volumeM3_input <= $maxVolumen);
        
        if ($pesoCumple && $volumenCumple) {
            // Calcular el totalCost aquí también (lo duplicamos para tenerlo en la respuesta)
            $baseCost = $service['costo_base'] ?: 0;
            $costPerKm = $service['costo_por_km'] ?: 0;
            $costPerKg = $service['costo_por_kg'] ?: 0;
            
            $distanceComponent = $km_input * $costPerKm;
            $weightComponent = $weight_input * $costPerKg;
            
            $service['totalCost'] = $baseCost + $distanceComponent + $weightComponent;

            // Solo incluimos servicios con un costo calculado > 0 (o si es solo base y es > 0)
            if ($service['totalCost'] > 0 || ($baseCost > 0 && $costPerKm === 0 && $costPerKg === 0)) {
                $finalFilteredServices[] = $service;
            }
        }
    }

    $calculationData = array_values($finalFilteredServices); // El array final que se envía a JS
    echo json_encode($calculationData);

} catch (Exception $e) {
    $response['error'] = 'Error en el servidor: ' . $e->getMessage();
    error_log("Error en get_calculation_data.php: " . $e->getMessage());
    echo json_encode($response);
}

$conn->close();
?>