<?php
// Archivo: public/php_scripts/update_shipment_data.php
// Propósito: Recibe datos actualizados de envíos (Envios, Clientes, DetalleEnvio) vía AJAX y los guarda en la base de datos.
// Utilizado por: area_personal_gerente.php

session_start();

header('Content-Type: application/json; charset=utf-8');

// --- VERIFICACIÓN DE AUTORIZACIÓN ---
$is_authorized = false;
$allowed_roles = ['administrador', 'gerente de ventas']; // Solo estos roles pueden actualizar

if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if (in_array(strtolower(trim($_SESSION['user_role'])), $allowed_roles, true)) {
        $is_authorized = true;
    }
}

if (!$is_authorized) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado para actualizar envíos.']);
    exit();
}
// --- FIN VERIFICACIÓN DE AUTORIZACIÓN ---

require_once '../server/database.php'; // Conexión a la base de datos

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true); // Decodificar el JSON del cuerpo de la solicitud

    $envio_id = (int)($input['envio_id'] ?? 0);
    $id_cliente = (int)($input['id_cliente'] ?? 0);
    $id_detalle_envio = (int)($input['id_detalle_envio'] ?? 0);
    $all_updated_data = $input['data'] ?? []; // Contiene datos agrupados por tabla: Envios, Clientes, DetalleEnvio

    if ($envio_id <= 0 || $id_cliente <= 0 || $id_detalle_envio <= 0 || empty($all_updated_data)) {
        http_response_code(400); // Bad Request
        $response['message'] = 'Datos insuficientes o inválidos (IDs de envío, cliente o detalle, o datos de actualización faltantes).';
        echo json_encode($response);
        $conn->close();
        exit();
    }

    $conn->begin_transaction(); // Iniciar transacción

    try {
        // =======================================
        // === ACTUALIZAR TABLA 'Envios' ===
        // =======================================
        $envios_data = $all_updated_data['Envios'] ?? [];
        if (!empty($envios_data)) {
            $update_envios_sql_parts = [];
            $update_envios_params = [];
            $update_envios_types = '';

            foreach ($envios_data as $column => $newValue) {
                switch ($column) {
                    case 'fecha_envio':
                        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newValue)) {
                            throw new Exception("Formato de fecha inválido para 'fecha_envio'. Use YYYY-MM-DD.");
                        }
                        $update_envios_sql_parts[] = "fecha_envio = ?";
                        $update_envios_params[] = $newValue;
                        $update_envios_types .= 's';
                        break;
                    case 'lugar_origen':
                    case 'lugar_distinto':
                        if (empty(trim($newValue))) {
                            throw new Exception("Los campos Origen y Destino no pueden estar vacíos.");
                        }
                        $update_envios_sql_parts[] = "$column = ?";
                        $update_envios_params[] = $newValue;
                        $update_envios_types .= 's';
                        break;
                    case 'km':
                        $numeric_value = floatval($newValue);
                        if ($numeric_value <= 0) {
                            throw new Exception("KM debe ser un número positivo.");
                        }
                        $update_envios_sql_parts[] = "km = ?";
                        $update_envios_params[] = $numeric_value;
                        $update_envios_types .= 'd'; // double o float
                        break;
                    case 'EstadoEnvio_estado_envio_id1':
                        $estado_id = (int)$newValue;
                        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM `EstadoEnvio` WHERE estado_envio_id = ?");
                        $check_stmt->bind_param("i", $estado_id);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result()->fetch_row()[0];
                        $check_stmt->close();
                        if ($check_result == 0) {
                            throw new Exception("ID de estado de envío no válido.");
                        }
                        $update_envios_sql_parts[] = "EstadoEnvio_estado_envio_id1 = ?";
                        $update_envios_params[] = $estado_id;
                        $update_envios_types .= 'i';
                        break;
                    case 'Vehiculos_vehiculos_id':
                        $vehiculo_id = (int)$newValue;
                        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM `Vehiculos` WHERE vehiculos_id = ?");
                        $check_stmt->bind_param("i", $vehiculo_id);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result()->fetch_row()[0];
                        $check_stmt->close();
                        if ($check_result == 0) {
                            throw new Exception("ID de vehículo no válido.");
                        }
                        $update_envios_sql_parts[] = "Vehiculos_vehiculos_id = ?";
                        $update_envios_params[] = $vehiculo_id;
                        $update_envios_types .= 'i';
                        break;
                    default:
                        error_log("Columna no esperada para actualización en Envios: $column");
                        break;
                }
            }

            if (!empty($update_envios_sql_parts)) {
                $sql_envios_update = "UPDATE `Envios` SET " . implode(', ', $update_envios_sql_parts) . " WHERE envio_id = ?";
                $stmt_envios = $conn->prepare($sql_envios_update);
                if ($stmt_envios === false) {
                    throw new Exception("Error al preparar UPDATE para Envios: " . $conn->error);
                }
                $update_envios_params[] = $envio_id;
                $update_envios_types .= 'i';

                $stmt_envios->bind_param($update_envios_types, ...$update_envios_params);

                if (!$stmt_envios->execute()) {
                    throw new Exception("Error al ejecutar UPDATE en Envios: " . $stmt_envios->error);
                }
                $stmt_envios->close();
            }
        }

        // =======================================
        // === ACTUALIZAR TABLA 'Clientes' ===
        // =======================================
        $clientes_data = $all_updated_data['Clientes'] ?? [];
        if (!empty($clientes_data)) {
            $update_clientes_sql_parts = [];
            $update_clientes_params = [];
            $update_clientes_types = '';

            foreach ($clientes_data as $column => $newValue) {
                switch ($column) {
                    case 'nombre_cliente':
                    case 'apellido_cliente':
                        if (empty(trim($newValue))) {
                            throw new Exception("Nombre y Apellido del cliente no pueden estar vacíos.");
                        }
                        $update_clientes_sql_parts[] = "$column = ?";
                        $update_clientes_params[] = $newValue;
                        $update_clientes_types .= 's';
                        break;
                    case 'numero_documento':
                        if (empty(trim($newValue)) || !preg_match('/^\d+$/', trim($newValue))) {
                            throw new Exception("Documento del cliente inválido (solo números).");
                        }
                        $update_clientes_sql_parts[] = "$column = ?";
                        $update_clientes_params[] = $newValue;
                        $update_clientes_types .= 's'; // A menudo DNI es string si incluye letras o ceros iniciales
                        break;
                    case 'telefono':
                        if (empty(trim($newValue)) || !preg_match('/^\+?\d{8,15}$/', trim($newValue))) {
                            throw new Exception("Teléfono del cliente inválido.");
                        }
                        $update_clientes_sql_parts[] = "$column = ?";
                        $update_clientes_params[] = $newValue;
                        $update_clientes_types .= 's';
                        break;
                    default:
                        error_log("Columna no esperada para actualización en Clientes: $column");
                        break;
                }
            }

            if (!empty($update_clientes_sql_parts)) {
                $sql_clientes_update = "UPDATE `Clientes` SET " . implode(', ', $update_clientes_sql_parts) . " WHERE id_cliente = ?";
                $stmt_clientes = $conn->prepare($sql_clientes_update);
                if ($stmt_clientes === false) {
                    throw new Exception("Error al preparar UPDATE para Clientes: " . $conn->error);
                }
                $update_clientes_params[] = $id_cliente;
                $update_clientes_types .= 'i';

                $stmt_clientes->bind_param($update_clientes_types, ...$update_clientes_params);

                if (!$stmt_clientes->execute()) {
                    throw new Exception("Error al ejecutar UPDATE en Clientes: " . $stmt_clientes->error);
                }
                $stmt_clientes->close();
            }
        }

        // =======================================
        // === ACTUALIZAR TABLA 'DetalleEnvio' ===
        // =======================================
        $detalle_envio_data = $all_updated_data['DetalleEnvio'] ?? [];
        if (!empty($detalle_envio_data)) {
            // Campos que van directo a la tabla
            $direct_update_parts = [];
            $direct_update_params = [];
            $direct_update_types = '';

            // Campos que van dentro del JSON 'descripcion'
            $json_update_fields = [
                'peso_kg' => 'peso_kg',
                'largo_cm' => 'largo_cm',
                'ancho_cm' => 'ancho_cm',
                'alto_cm' => 'alto_cm',
                'descripcion_adicional_usuario' => 'descripcion_adicional_usuario' // <-- Este campo идет в JSON
            ];

            // Recuperar la descripción JSON actual para combinar
            $current_json_desc = null;
            $stmt_get_json = $conn->prepare("SELECT descripcion FROM `DetalleEnvio` WHERE id_detalle_envio = ?");
            $stmt_get_json->bind_param("i", $id_detalle_envio);
            $stmt_get_json->execute();
            $stmt_get_json->bind_result($current_json_desc);
            $stmt_get_json->fetch();
            $stmt_get_json->close();

            $parsed_json = json_decode($current_json_desc ?: '{}', true); // Decodificar o inicializar un array vacío

            foreach ($detalle_envio_data as $column => $newValue) {
                if (array_key_exists($column, $json_update_fields)) {
                    // Validar y actualizar campo dentro del JSON
                    switch ($column) {
                        case 'peso_kg':
                        case 'largo_cm':
                        case 'ancho_cm':
                        case 'alto_cm':
                            $numeric_value = floatval($newValue);
                            if ($numeric_value <= 0) {
                                throw new Exception("Dimensiones y peso deben ser números positivos.");
                            }
                            $parsed_json[$json_update_fields[$column]] = $numeric_value;
                            break;
                        case 'descripcion_adicional_usuario':
                            $parsed_json[$json_update_fields[$column]] = trim($newValue);
                            break;
                    }
                } else {
                    // Esto обработано в предыдущей версии: direct_update_parts[] = ...
                    // Если у вас есть другие прямые колонки в DetalleEnvio, обработайте их здесь
                    error_log("Columna no esperada para actualización en DetalleEnvio (directa): $column");
                }
            }

            // Convertir el JSON modificado de vuelta a string
            $updated_json_string = json_encode($parsed_json);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Error al codificar JSON para DetalleEnvio: " . json_last_error_msg());
            }

            // Actualizar la columna 'descripcion' en DetalleEnvio con el nuevo JSON
            $sql_detalle_update = "UPDATE `DetalleEnvio` SET descripcion = ? WHERE id_detalle_envio = ?";
            $stmt_detalle = $conn->prepare($sql_detalle_update);
            if ($stmt_detalle === false) {
                throw new Exception("Error al preparar UPDATE para DetalleEnvio: " . $conn->error);
            }
            $stmt_detalle->bind_param("si", $updated_json_string, $id_detalle_envio);

            if (!$stmt_detalle->execute()) {
                throw new Exception("Error al ejecutar UPDATE en DetalleEnvio: " . $stmt_detalle->error);
            }
            $stmt_detalle->close();
        }

        $conn->commit(); // Confirmar la transacción
        $response['success'] = true;
        $response['message'] = 'Cambios guardados correctamente.';

    } catch (Exception $e) {
        $conn->rollback(); // Revertir la transacción en caso de error
        http_response_code(500); // Internal Server Error
        $response['message'] = 'Error en el servidor al guardar cambios: ' . $e->getMessage();
        error_log("Error en update_shipment_data.php (transacción): " . $e->getMessage());
    }

} else {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'Método de solicitud no válido. Este script solo acepta solicitudes POST.';
}

$conn->close();
echo json_encode($response);
exit();