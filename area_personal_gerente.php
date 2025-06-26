<?php
// Archivo: public/area_personal_gerente.php
// Propósito: Permite al Gerente de Ventas gestionar y ver todos los envíos.
session_start();
if (
    empty($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    trim(strtolower($_SESSION['user_role'])) !== 'gerente de ventas'
) {
    $_SESSION['update_status'] = 'Error: Acceso no autorizado para Gerente de ventas.';
    header("Location: ../login.php");
    exit();
}
require_once 'server/database.php'; // Conexión a la base de datos

// --- Obtener данные для JavaScript (Estados y Vehículos) ---
$estados_disponibles_para_js = [];
try {
    $stmt_estados = $conn->prepare("SELECT estado_envio_id, descripcion FROM EstadoEnvio ORDER BY descripcion");
    if ($stmt_estados === false) {
        throw new Exception("Error al preparar la consulta de estados: " . $conn->error);
    }
    $stmt_estados->execute();
    $result_estados = $stmt_estados->get_result();
    while ($row = $result_estados->fetch_assoc()) {
        $estados_disponibles_para_js[] = ['id' => $row['estado_envio_id'], 'descripcion' => $row['descripcion']];
    }
    $stmt_estados->close();
} catch (Exception $e) {
    error_log("Error cargando estados para JS: " . $e->getMessage());
    $estados_disponibles_para_js = []; // Asegurar que sea un array vacío en caso de error
}


$vehiculos_disponibles_para_js = [];
try {
    $stmt_vehiculos = $conn->prepare("SELECT vehiculos_id, tipo, patente FROM Vehiculos ORDER BY tipo, patente");
    if ($stmt_vehiculos === false) {
        throw new Exception("Error al preparar la consulta de vehículos: " . $conn->error);
    }
    $stmt_vehiculos->execute();
    $result_vehiculos = $stmt_vehiculos->get_result();
    while ($row = $result_vehiculos->fetch_assoc()) {
        // Комбинировать тип и patente для опции отображения
        $vehiculos_disponibles_para_js[] = [
            'id' => $row['vehiculos_id'],
            'display_text' => "{$row['tipo']} ({$row['patente']})"
        ];
    }
    $stmt_vehiculos->close();
} catch (Exception $e) {
    error_log("Error cargando vehículos para JS: " . $e->getMessage());
    $vehiculos_disponibles_para_js = []; // Asegurar que sea un array vacío en caso de error
}
// --- Fin obtener datos para JavaScript ---


$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_fecha = $_GET['filtro_fecha'] ?? '';
$filtro_cliente_email = $_GET['filtro_cliente_email'] ?? '';
$filtro_origen = $_GET['filtro_origen'] ?? '';
$filtro_destino = $_GET['filtro_destino'] ?? '';

$sql = "SELECT
            e.envio_id,
            e.lugar_origen,
            e.lugar_distinto,
            e.fecha_envio,
            e.km,
            e.Vehiculos_vehiculos_id,
            es.estado_envio_id AS estado_actual_id,
            es.descripcion AS estado_actual_desc,
            c.id_cliente, -- AÑADIDO: ID del cliente
            c.nombre_cliente,
            c.apellido_cliente,
            c.numero_documento AS cliente_documento, -- AÑADIDO: Documento del cliente
            c.telefono AS cliente_telefono, -- AÑADIDO: Teléfono del cliente
            u.email AS cliente_email,
            v.tipo AS tipo_vehiculo,
            v.patente AS vehiculo_patente,
            de.id_detalle_envio, -- AÑADIDO: ID del DetalleEnvio
            de.peso_kg,
            de.alto_cm,
            de.largo_cm,
            de.ancho_cm,
            de.descripcion AS detalles_paquete_json,
            de.Servicios_servicio_id
        FROM Envios e
        JOIN EstadoEnvio es ON e.EstadoEnvio_estado_envio_id1 = es.estado_envio_id
        LEFT JOIN Vehiculos v ON e.Vehiculos_vehiculos_id = v.vehiculos_id
        LEFT JOIN Clientes c ON e.Clientes_id_cliente = c.id_cliente
        LEFT JOIN Usuarios u ON c.Usuarios_id_usuario = u.id_usuario
        LEFT JOIN DetalleEnvio de ON e.envio_id = de.Envios_envio_id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($filtro_estado)) {
    $sql .= " AND es.descripcion = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}
if (!empty($filtro_fecha)) {
    $sql .= " AND e.fecha_envio = ?";
    $params[] = $filtro_fecha;
    $types .= "s";
}
if (!empty($filtro_cliente_email)) {
    $sql .= " AND u.email LIKE ?";
    $params[] = "%" . $filtro_cliente_email . "%";
    $types .= "s";
}
if (!empty($filtro_origen)) {
    $sql .= " AND e.lugar_origen LIKE ?";
    $params[] = "%" . $filtro_origen . "%";
    $types .= "s";
}
if (!empty($filtro_destino)) {
    $sql .= " AND e.lugar_distinto LIKE ?";
    $params[] = "%" . $filtro_destino . "%";
    $types .= "s";
}

$sql .= " ORDER BY e.fecha_envio DESC, e.envio_id DESC";
?>

<?php include 'includes/header.php'; ?>

<section class="personal-area-container">
    <div class="content-wrapper">

        <div class="page-header">
            <h2>Gestión de Envíos (Gerente de Ventas)</h2>
            <a href="area_personal_gerente_feedback.php" class="btn btn-sm btn-outline-secondary ms-3" title="Ver y Gestionar Feedback">
                <i class="fas fa-comment-dots"></i> Gestionar Feedback
            </a>
        </div>

        <?php if (isset($_SESSION['update_status'])) : ?>
            <div class="user-message alert alert-info">
                <?php echo htmlspecialchars($_SESSION['update_status']); ?>
            </div>
            <?php unset($_SESSION['update_status']); ?>
        <?php endif; ?>

        <div class="filter-panel card p-4 mb-4">
            <h5 class="mb-3">Filtrar Envíos</h5>
            <form action="area_personal_gerente.php" method="GET" class="filter-form row g-3">
                <div class="col-md-4">
                    <label for="filtro_estado" class="form-label">Estado:</label>
                    <select id="filtro_estado" name="filtro_estado" class="form-select">
                        <option value="">Todos los Estados</option>
                        <?php
                        // Asegurarse de que EstadoEnvio se recupera correctamente
                        $estados_select_result = $conn->query("SELECT DISTINCT descripcion FROM EstadoEnvio ORDER BY descripcion");
                        if ($estados_select_result) {
                            while ($estado_row = $estados_select_result->fetch_assoc()) {
                                $estado = htmlspecialchars($estado_row['descripcion']);
                                $selected = ($filtro_estado == $estado) ? 'selected' : '';
                                echo "<option value=\"$estado\" $selected>$estado</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filtro_fecha" class="form-label">Fecha:</label>
                    <input type="date" id="filtro_fecha" name="filtro_fecha" class="form-control" value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                </div>
                <div class="col-md-4">
                    <label for="filtro_cliente_email" class="form-label">Email Cliente:</label>
                    <input type="email" id="filtro_cliente_email" name="filtro_cliente_email" class="form-control" value="<?php echo htmlspecialchars($filtro_cliente_email); ?>" placeholder="ej: cliente@email.com">
                </div>
                <div class="col-md-6">
                    <label for="filtro_origen" class="form-label">Origen:</label>
                    <input type="text" id="filtro_origen" name="filtro_origen" class="form-control" value="<?php echo htmlspecialchars($filtro_origen); ?>" placeholder="Ciudad de Origen">
                </div>
                <div class="col-md-6">
                    <label for="filtro_destino" class="form-label">Destino:</label>
                    <input type="text" id="filtro_destino" name="filtro_destino" class="form-control" value="<?php echo htmlspecialchars($filtro_destino); ?>" placeholder="Ciudad de Destino">
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="area_personal_gerente.php" class="btn btn-secondary">Limpiar Filtros</a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="shipment-table">
                <thead>
                    <tr>
                        <th>Pedido #</th>
                        <th>Fecha</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Cliente (Email)</th>
                        <th>Tipo Transporte</th>
                        <th>Patente</th>
                        <th>KM</th>
                        <th>Costo Estimado</th>
                        <th>Estado</th>
                        <th>Acción</th>
                        <th>Detalles Paquete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        if (!empty($params)) {
                            $stmt->bind_param($types, ...$params);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) :
                            while ($envio = $result->fetch_assoc()) :
                                // JSON processing for package details
                                $costo_estimado_pedido = 'N/A';
                                $desc_adicional_usuario = 'Sin descripción.';
                                $peso_paquete = 'N/A';
                                $largo_paquete = 'N/A';
                                $ancho_paquete = 'N/A';
                                $alto_paquete = 'N/A';
                                $dimensiones_paquete_display = 'N/AxN/AxN/A cm';

                                $detalles_parsed = json_decode($envio['detalles_paquete_json'], true);

                                if (json_last_error() === JSON_ERROR_NONE && is_array($detalles_parsed)) {
                                    $costo_estimado_pedido = number_format($detalles_parsed['costo_estimado_final'] ?? 'N/A', 2, '.', ',');
                                    $desc_adicional_usuario = htmlspecialchars($detalles_parsed['descripcion_adicional_usuario'] ?? 'Sin descripción.');
                                    $peso_paquete = htmlspecialchars($detalles_parsed['peso_kg'] ?? 'N/A');
                                    $largo_paquete = htmlspecialchars($detalles_parsed['largo_cm'] ?? 'N/A');
                                    $ancho_paquete = htmlspecialchars($detalles_parsed['ancho_cm'] ?? 'N/A');
                                    $alto_paquete = htmlspecialchars($detalles_parsed['alto_cm'] ?? 'N/A');
                                    $dimensiones_paquete_display = "{$largo_paquete}x{$ancho_paquete}x{$alto_paquete} cm";
                                } else {
                                    $desc_adicional_usuario = htmlspecialchars($envio['detalles_paquete_json']);
                                }
                    ?>
                                <tr data-envio-id="<?php echo htmlspecialchars($envio['envio_id']); ?>">
                                    <td><?php echo htmlspecialchars($envio['envio_id']); ?></td>
                                    <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($envio['fecha_envio']))); ?></td>
                                    <td><?php echo htmlspecialchars($envio['lugar_origen']); ?></td>
                                    <td><?php echo htmlspecialchars($envio['lugar_distinto']); ?></td>
                                    <td><?php echo htmlspecialchars($envio['nombre_cliente'] . ' ' . $envio['apellido_cliente'] . ' (' . $envio['cliente_email'] . ')'); ?></td>
                                    <td><?php echo htmlspecialchars($envio['tipo_vehiculo'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($envio['vehiculo_patente'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($envio['km']); ?></td>
                                    <td>$<?php echo $costo_estimado_pedido; ?></td>
                                    <td>
                                        <?php $status_class = 'status-' . strtolower(str_replace(' ', '-', $envio['estado_actual_desc'])); ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($envio['estado_actual_desc']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info view-order-details" data-envio-id="<?php echo htmlspecialchars($envio['envio_id']); ?>">Ver Detalles</button>
                                    </td>
                                    <td>
                                        <strong>Peso:</strong> <?php echo $peso_paquete; ?> kg<br>
                                        <strong>Dimensiones:</strong> <?php echo $dimensiones_paquete_display; ?><br>
                                        <?php if (!empty($desc_adicional_usuario) && $desc_adicional_usuario !== 'Sin descripción.') : ?>
                                            <strong>Descripción Adicional:</strong> <?php echo $desc_adicional_usuario; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="12" class="no-results">No se encontraron envíos con los filtros seleccionados.</td>
                            </tr>
                        <?php endif; ?>
                </tbody>
            </table>
            <?php
                        $stmt->close();
                    } else {
                        echo '<div class="user-message error-message">Error al preparar la consulta de envíos.</div>';
                    }
                    $conn->close();
            ?>
        </div>
    </div>
</section>

<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailsModalLabel">Detalles y Edición del Pedido #<span id="modalEnvioId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editOrderForm">
                    <input type="hidden" id="editEnvioId" name="envio_id">
                    <input type="hidden" id="editIdCliente" name="id_cliente"> <input type="hidden" id="editIdDetalleEnvio" name="id_detalle_envio"> <h6 class="mb-3">Información del Envío:</h6>
                    <div class="mb-3">
                        <label for="editFechaEnvio" class="form-label">Fecha de Envío:</label>
                        <input type="date" class="form-control" id="editFechaEnvio" name="fecha_envio" required>
                    </div>
                    <div class="mb-3">
                        <label for="editLugarOrigen" class="form-label">Lugar de Origen:</label>
                        <input type="text" class="form-control" id="editLugarOrigen" name="lugar_origen" required>
                    </div>
                    <div class="mb-3">
                        <label for="editLugarDestino" class="form-label">Lugar de Destino:</label>
                        <input type="text" class="form-control" id="editLugarDestino" name="lugar_distinto" required>
                    </div>
                    <div class="mb-3">
                        <label for="editKm" class="form-label">KM:</label>
                        <input type="number" step="0.1" class="form-control" id="editKm" name="km" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTipoVehiculo" class="form-label">Tipo de Vehículo:</label>
                        <select class="form-select" id="editTipoVehiculo" name="Vehiculos_vehiculos_id">
                            </select>
                    </div>
                    <div class="mb-3">
                        <label for="editEstado" class="form-label">Estado:</label>
                        <select class="form-select" id="editEstado" name="EstadoEnvio_estado_envio_id1">
                            </select>
                    </div>

                    <hr>
                    <h6 class="mb-3">Detalles del Cliente:</h6>
                    <div class="mb-3">
                        <label for="editNombreCliente" class="form-label">Nombre Cliente:</label>
                        <input type="text" class="form-control" id="editNombreCliente" name="nombre_cliente" required>
                    </div>
                    <div class="mb-3">
                        <label for="editApellidoCliente" class="form-label">Apellido Cliente:</label>
                        <input type="text" class="form-control" id="editApellidoCliente" name="apellido_cliente" required>
                    </div>
                    <p class="mb-3"><strong>Email:</strong> <span id="displayEmailCliente"></span></p>
                    <div class="mb-3">
                        <label for="editDocumentoCliente" class="form-label">Documento:</label>
                        <input type="text" class="form-control" id="editDocumentoCliente" name="numero_documento" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTelefonoCliente" class="form-label">Teléfono:</label>
                        <input type="tel" class="form-control" id="editTelefonoCliente" name="telefono" required>
                    </div>

                    <hr>
                    <h6 class="mb-3">Detalles del Paquete:</h6>
                    <div class="mb-3">
                        <label for="editPesoKg" class="form-label">Peso (kg):</label>
                        <input type="number" step="0.1" class="form-control" id="editPesoKg" name="peso_kg" required>
                    </div>
                    <div class="mb-3">
                        <label for="editLargoCm" class="form-label">Largo (cm):</label>
                        <input type="number" step="0.1" class="form-control" id="editLargoCm" name="largo_cm" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAnchoCm" class="form-label">Ancho (cm):</label>
                        <input type="number" step="0.1" class="form-control" id="editAnchoCm" name="ancho_cm" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAltoCm" class="form-label">Alto (cm):</label>
                        <input type="number" step="0.1" class="form-control" id="editAltoCm" name="alto_cm" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDescripcionAdicional" class="form-label">Descripción Adicional:</label>
                        <textarea class="form-control" id="editDescripcionAdicional" name="descripcion_adicional_usuario" rows="3"></textarea>
                    </div>
                    
                    <p class="mb-3"><strong>Costo Estimado:</strong> $<span id="displayCostoEstimado"></span></p>

                </form>
                <div id="modalResponseStatus" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" form="editOrderForm" class="btn btn-primary" id="saveOrderChangesBtn">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const ESTADOS_DISPONIBLES = <?php echo json_encode($estados_disponibles_para_js); ?>;
    const VEHICULOS_DISPONIBLES = <?php echo json_encode($vehiculos_disponibles_para_js); ?>;
</script>

<script src="js/gerente_shipment_management.js"></script>

<?php include 'includes/footer.php'; ?>