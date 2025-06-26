<?php
// Archivo: public/area_personal_cliente.php
// Propósito: Muestra los envíos asignados a un conductor y permite actualizar su estado.
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'cliente')) {
    header("Location: login.php");
    exit();
}

require_once 'server/database.php';
// Obtener ID del cliente desde el ID de usuario en la sesión
$id_usuario_actual = $_SESSION['user_id'];
$id_cliente = null;
$stmt_cliente = $conn->prepare("SELECT id_cliente FROM Clientes WHERE Usuarios_id_usuario = ?");
$stmt_cliente->bind_param("i", $id_usuario_actual);
$stmt_cliente->execute();
$result_cliente = $stmt_cliente->get_result();
if ($cliente_row = $result_cliente->fetch_assoc()) {
    $id_cliente = $cliente_row['id_cliente'];
}
$stmt_cliente->close();

if ($id_cliente === null) {
    echo '<section class="content-wrapper"><div class="alert alert-danger">No se pudo encontrar el perfil de cliente asociado a su cuenta.</div></section>';
    include 'includes/footer.php';
    exit();
}
// --- Obtener todos los datos de servicios y tarifas para que JavaScript pueda calcular dinámicamente ---
// Esta variable ($servicios_calculadora_data) se inyectará en JavaScript.
$servicios_calculadora_data = []; 
$vehiculos_capacidades = []; // Capacidades de vehículos

try {
    // Obtener capacidades de vehículos para el mapeo
    $sqlVehiculos = "SELECT tipo, capacidad_kg, capacidad_m3 FROM Vehiculos";
    $resultVehiculos = $conn->query($sqlVehiculos);
    if ($resultVehiculos === false) {
        throw new Exception("Error al obtener capacidades de vehículos (area_personal): " . $conn->error);
    }
    while ($rowVehiculo = $resultVehiculos->fetch_assoc()) {
        $vehiculos_capacidades[strtolower($rowVehiculo['tipo'])] = [
            'max_peso_kg' => floatval($rowVehiculo['capacidad_kg']),
            'max_volumen_m3' => floatval($rowVehiculo['capacidad_m3'])
        ];
    }
    // Consulta para obtener todos los servicios y sus tarifas asociadas
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
    $result_raw_services = $conn->query($sql);

    if ($result_raw_services === false) {
        throw new Exception("Error al ejecutar la consulta de servicios (area_personal): " . $conn->error);
    }

    $rawServicesData = [];
    while ($row = $result_raw_services->fetch_assoc()) {
        $rawServicesData[] = $row;
    }
    // Procesar y agrupar los servicios lógicos para JavaScript
    $groupedLogicalServices = [];
    foreach ($rawServicesData as $row) {
        $commonServiceName = preg_replace('/\s*\((?:Por (?:KM|KG|Hora)|Base)\)\s*/i', '', $row['nombre_servicio']);
        $commonServiceName = trim($commonServiceName);
        $commonServiceKey = strtolower($commonServiceName);

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

            if (!empty($currentServiceType) && isset($vehiculos_capacidades[$currentServiceType])) {
                $groupedLogicalServices[$commonServiceKey]['capacidades'] = $vehiculos_capacidades[$currentServiceType];
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

    $servicios_calculadora_data = array_values($groupedLogicalServices);
    // Obtener lista de servicios para el menú desplegable
    $servicios_select_options = [];
    $sql_select_options = "SELECT servicio_id, nombre_servicio FROM Servicios WHERE unidad_medida_tarifa = 'base' OR unidad_medida_tarifa = 'hora' OR unidad_medida_tarifa IS NULL OR unidad_medida_tarifa = '' ORDER BY nombre_servicio";
    $result_select_options = $conn->query($sql_select_options);
    if ($result_select_options) {
        while ($row = $result_select_options->fetch_assoc()) {
            $servicios_select_options[] = $row;
        }
    }

} catch (Exception $e) {
    error_log("Error al cargar datos de calculadora en area_personal_cliente.php: " . $e->getMessage());
    $servicios_calculadora_data = []; 
    $servicios_select_options = [];
}
// --- Obtener datos iniciales de la calculadora desde los parámetros de la URL
$initial_service_id = isset($_GET['service_id']) ? htmlspecialchars($_GET['service_id']) : null;
$initial_calculated_cost = isset($_GET['costo_estimado']) ? htmlspecialchars($_GET['costo_estimado']) : null;
$initial_service_name = isset($_GET['service_name']) ? htmlspecialchars(urldecode($_GET['service_name'])) : null;
$initial_icon_class = isset($_GET['icon_class']) ? htmlspecialchars(urldecode($_GET['icon_class'])) : null;


$clear_calculator_data_flag = isset($_SESSION['clear_calculator_data_flag']) && $_SESSION['clear_calculator_data_flag'] === true;
if ($clear_calculator_data_flag) {
    unset($_SESSION['clear_calculator_data_flag']); 
}
?>

<section class="personal-area-container">
    <div class="content-wrapper">
        <div class="page-header">
            <h2>Mi Área Personal</h2>
        </div>

        <?php
        if (isset($_SESSION['order_status_msg'])) {
            echo '<div class="user-message alert alert-success">' . htmlspecialchars($_SESSION['order_status_msg']) . '</div>';
            unset($_SESSION['order_status_msg']);
        }
        ?>

        <div class="panel">
            <h3>Realizar un Nuevo Pedido</h3>
            <form id="newOrderForm" action="php_scripts/procesar_nuevo_envio.php" method="POST" class="order-form">
                <input type="hidden" id="costo_estimado_hidden" name="costo_estimado" value="">
                <input type="hidden" id="selected_service_main_id_hidden" name="selected_service_main_id" value="">
                
                <div class="form-group mb-4">
                    <label for="costo_estimado_display" class="form-label">Costo Estimado Final</label>
                    <input type="text" id="costo_estimado_display" class="form-control" 
                           value="$0.00" readonly style="background-color: #e9ecef; font-weight: bold;">
                </div>

                <div id="selected_transport_display" class="form-group mb-4" style="display:none;">
                    <label>Transporte Seleccionado</label>
                    <div class="selected-service-display card p-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-box fa-2x me-3" id="selected_transport_icon"></i>
                            <div>
                                <h5 class="mb-0" id="selected_transport_name"></h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="lugar_origen">Lugar de Origen</label>
                        <input type="text" id="lugar_origen" name="lugar_origen" class="form-control" placeholder="Ciudad, Dirección" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="lugar_destino">Lugar de Destino</label>
                        <input type="text" id="lugar_destino" name="lugar_destino" class="form-control" placeholder="Ciudad, Dirección" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="km">Distancia (km)</label>
                        <input type="number" id="km" name="km" class="form-control" placeholder="Ej: 25" required step="0.1" min="0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="weight">Peso (kg)</label>
                        <input type="number" id="weight" name="weight" class="form-control" placeholder="Ej: 10" required step="0.1" min="0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="length">Largo (cm)</label>
                        <input type="number" id="length" name="length" class="form-control" placeholder="Ej: 50" required min="0">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="width">Ancho (cm)</label>
                        <input type="number" id="width" name="width" class="form-control" placeholder="Ej: 40" required min="0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="height">Alto (cm)</label>
                        <input type="number" id="height" name="height" class="form-control" placeholder="Ej: 30" required min="0">
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="id_servicio">Tipo de Envío</label>
                        <select id="id_servicio" name="id_servicio" class="form-control" required>
                            <option value="">-- Seleccione un servicio --</option>
                            <?php foreach ($servicios_select_options as $servicio_option): ?>
                                <option value="<?php echo htmlspecialchars($servicio_option['servicio_id']); ?>">
                                    <?php echo htmlspecialchars($servicio_option['nombre_servicio']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="fecha_envio">Fecha de Recogida</label>
                        <input type="date" id="fecha_envio" name="fecha_envio" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción Adicional del Paquete (opcional)</label>
                    <input type="text" id="descripcion" name="descripcion" class="form-control" placeholder="Ej: Contiene artículos frágiles">
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="cta-button">Confirmar y Crear Pedido</button>
                </div>
            </form>
        </div>

        <h3 class="section-title">Historial de Pedidos</h3>
        <div class="table-container">
            <table class="shipment-table">
                <thead>
                    <tr>
                        <th>Pedido #</th>
                        <th>Fecha</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Estado</th>
                        <th>Costo Estimado</th>
                        <th>Detalles del Paquete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT e.envio_id, e.fecha_envio, e.lugar_origen, e.lugar_distinto, es.descripcion AS estado, 
                                   de.descripcion AS detalles_paquete_json,
                                   de.peso_kg AS peso_detalle, 
                                   de.alto_cm AS alto_detalle, 
                                   de.largo_cm AS largo_detalle, 
                                   de.ancho_cm AS ancho_detalle,
                                   de.km AS km_detalle,
                                   de.Servicios_servicio_id AS servicio_id_detalle
                            FROM Envios e
                            JOIN EstadoEnvio es ON e.EstadoEnvio_estado_envio_id1 = es.estado_envio_id
                            LEFT JOIN DetalleEnvio de ON e.envio_id = de.Envios_envio_id 
                            WHERE e.Clientes_id_cliente = ?
                            ORDER BY e.fecha_envio DESC";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id_cliente);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0):
                        while ($envio = $result->fetch_assoc()):
                            $costo_recuperado = 'N/A';
                            $desc_usuario_recuperada = 'Sin descripción adicional.';
                            
                            $peso_detalle_recuperado = htmlspecialchars($envio['peso_detalle'] ?? 'N/A'); 
                            $alto_detalle_recuperado = htmlspecialchars($envio['alto_detalle'] ?? 'N/A');
                            $largo_detalle_recuperado = htmlspecialchars($envio['largo_detalle'] ?? 'N/A'); 
                            $ancho_detalle_recuperado = htmlspecialchars($envio['ancho_detalle'] ?? 'N/A');
                            $km_detalle_recuperado = htmlspecialchars($envio['km_detalle'] ?? 'N/A'); 
                            $servicio_id_detalle_recuperado = htmlspecialchars($envio['servicio_id_detalle'] ?? 'N/A');

                            $detalle_parsed = json_decode($envio['detalles_paquete_json'], true);

                            if (json_last_error() === JSON_ERROR_NONE && is_array($detalle_parsed)) {
                                $costo_recuperado = number_format($detalle_parsed['costo_estimado_final'] ?? 'N/A', 2, '.', ',');
                                $desc_usuario_recuperada = $detalle_parsed['descripcion_adicional_usuario'] ?? 'Sin descripción adicional.';
                            } else {
                                $desc_usuario_recuperada = htmlspecialchars($envio['detalles_paquete_json']);
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($envio['envio_id']); ?></td>
                                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($envio['fecha_envio']))); ?></td>
                                <td><?php echo htmlspecialchars($envio['lugar_origen']); ?></td>
                                <td><?php echo htmlspecialchars($envio['lugar_distinto']); ?></td>
                                <td>
                                    <?php $status_class = 'status-' . strtolower(str_replace(' ', '-', $envio['estado'])); ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($envio['estado']); ?>
                                    </span>
                                </td>
                                <td>$<?php echo $costo_recuperado; ?></td>
                                <td>
                                    <strong>KM:</strong> <?php echo $km_detalle_recuperado; ?><br>
                                    <strong>Peso:</strong> <?php echo $peso_detalle_recuperado; ?> kg<br>
                                    <strong>Dimensiones (L/A/H):</strong> <?php echo $largo_detalle_recuperado; ?> x <?php echo $ancho_detalle_recuperado; ?> x <?php echo $alto_detalle_recuperado; ?> cm<br>
                                    <strong>Servicio ID:</strong> <?php echo $servicio_id_detalle_recuperado; ?><br>
                                    <?php if (!empty($desc_usuario_recuperada) && $desc_usuario_recuperada !== 'Sin descripción adicional.'): ?>
                                        <strong>Descripción Adicional:</strong> <?php echo htmlspecialchars($desc_usuario_recuperada); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Aún no has realizado ningún pedido.</td>
                        </tr>
                    <?php
                    endif;
                    $stmt->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
    const SERVICES_CALCULATOR_DATA = <?php echo json_encode($servicios_calculadora_data); ?>;
    const initialServiceId = "<?php echo $initial_service_id; ?>";
    const initialCost = "<?php echo $initial_calculated_cost; ?>";
    const initialServiceName = "<?php echo $initial_service_name; ?>";
    const initialIconClass = "<?php echo $initial_icon_class; ?>";

    const CLEAR_CALCULATOR_DATA_FLAG = <?php echo $clear_calculator_data_flag ? 'true' : 'false'; ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const newOrderForm = document.getElementById('newOrderForm');
        const lugarOrigenInput = document.getElementById('lugar_origen');
        const lugarDestinoInput = document.getElementById('lugar_destino');
        const kmInput = document.getElementById('km');
        const weightInput = document.getElementById('weight');
        const lengthInput = document.getElementById('length');
        const widthInput = document.getElementById('width');
        const heightInput = document.getElementById('height');
        const descripcionInput = document.getElementById('descripcion');
        const serviceSelect = document.getElementById('id_servicio');
        const costoEstimadoDisplay = document.getElementById('costo_estimado_display');
        const costoEstimadoHidden = document.getElementById('costo_estimado_hidden');
        const selectedServiceMainIdHidden = document.getElementById('selected_service_main_id_hidden');
        
        const selectedTransportDisplay = document.getElementById('selected_transport_display');
        const selectedTransportIcon = document.getElementById('selected_transport_icon');
        const selectedTransportName = document.getElementById('selected_transport_name');

        const transportImages = {
            'motocicleta': 'img/moto.png', 
            'furgoneta': 'img/furgoneta.png',
            'camión': 'img/camion.png',
            'pickup': 'img/pickup.png',
            'default': 'img/default.png' 
        };

        const calculateAndUpdateCost = () => {
            const currentKm = parseFloat(kmInput.value) || 0;
            const currentWeight = parseFloat(weightInput.value) || 0;
            const currentLength = parseFloat(lengthInput.value) || 0;
            const currentWidth = parseFloat(widthInput.value) || 0;
            const currentHeight = parseFloat(heightInput.value) || 0;
            const currentServiceId = serviceSelect.value;

            const selectedService = SERVICES_CALCULATOR_DATA.find(s => s.main_servicio_id == currentServiceId);

            let totalCost = 0;
            let currentServiceName = '';
            let currentIconClass = 'fas fa-box';

            if (selectedService) {
                const baseCost = selectedService.costo_base || 0;
                const costPerKm = selectedService.costo_por_km || 0;
                const costPerKg = selectedService.costo_por_kg || 0;

                const distanceComponent = currentKm * costPerKm;
                const weightComponent = currentWeight * costPerKg;
                
                totalCost = baseCost + distanceComponent + weightComponent;
                currentServiceName = selectedService.nombre_servicio;
                currentIconClass = selectedService.icon_class;

                const maxPeso = selectedService.capacidades.max_peso_kg;
                const maxVolumen = selectedService.capacidades.max_volumen_m3;
                const currentVolumeM3 = (currentLength * currentWidth * currentHeight) / 1000000;

                const areDimensionsValidForCapacityCheck = currentLength > 0 && currentWidth > 0 && currentHeight > 0;

                const pesoCumple = (maxPeso === null || currentWeight <= maxPeso);
                const volumenCumple = (maxVolumen === null || !areDimensionsValidForCapacityCheck || currentVolumeM3 <= maxVolumen);

                if (!pesoCumple || !volumenCumple || currentKm <= 0 || currentWeight <= 0) { // Añadir validación de KM/Peso > 0
                    costoEstimadoDisplay.value = 'N/A (Excede límites)';
                    costoEstimadoDisplay.style.color = 'red';
                    costoEstimadoHidden.value = ''; 
                    selectedServiceMainIdHidden.value = ''; 
                    
                    if (selectedTransportDisplay) {
                        selectedTransportDisplay.style.display = 'block';
                        if (selectedTransportIcon) {
                            selectedTransportIcon.className = 'fas fa-exclamation-triangle fa-2x me-3 text-danger';
                        }
                        if (selectedTransportName) {
                            selectedTransportName.textContent = currentServiceName ? currentServiceName + " (Excede límites)" : "Límites Excedidos";
                            selectedTransportName.style.color = 'red';
                        }
                    }
                    return; 
                } else {
                    costoEstimadoDisplay.style.color = 'inherit'; 
                    if (selectedTransportName) selectedTransportName.style.color = 'inherit';
                }
            } else { 
                costoEstimadoDisplay.value = "$0.00";
                costoEstimadoHidden.value = "0";
                selectedServiceMainIdHidden.value = "";
                if (selectedTransportDisplay) {
                    selectedTransportDisplay.style.display = 'none';
                }
                return;
            }
            
            costoEstimadoDisplay.value = `$${totalCost.toFixed(2)}`;
            costoEstimadoHidden.value = totalCost.toFixed(2);
            selectedServiceMainIdHidden.value = currentServiceId;

            if (selectedTransportDisplay) {
                if (selectedService && currentServiceId !== "" && totalCost > 0) {
                    selectedTransportDisplay.style.display = 'block';
                    if (selectedTransportIcon) {
                        selectedTransportIcon.className = currentIconClass + ' fa-2x me-3';
                    }
                    if (selectedTransportName) {
                        selectedTransportName.textContent = currentServiceName;
                    }
                } else {
                    selectedTransportDisplay.style.display = 'none';
                }
            }
        };

        if (CLEAR_CALCULATOR_DATA_FLAG) {
            sessionStorage.removeItem('calculatorData'); 
        } else {
            const savedDataJSON = sessionStorage.getItem('calculatorData');
            if (savedDataJSON) {
                try {
                    const savedData = JSON.parse(savedDataJSON);

                    lugarOrigenInput.value = savedData.lugar_origen || '';
                    lugarDestinoInput.value = savedData.lugar_destino || '';
                    kmInput.value = savedData.km || '';
                    weightInput.value = savedData.weight || '';
                    lengthInput.value = savedData.length || '';
                    widthInput.value = savedData.width || '';
                    heightInput.value = savedData.height || '';
                    descripcionInput.value = savedData.descripcion || ''; 

                } catch (e) {
                    console.error("Error al parsear datos de sessionStorage:", e);
                    sessionStorage.removeItem('calculatorData');
                }
            }
        }

        if (initialServiceId) {
            serviceSelect.value = initialServiceId;
        }
        
       
        const isAnyRelevantFieldFilled = (lugarOrigenInput.value || lugarDestinoInput.value || (kmInput.value > 0) || (weightInput.value > 0) || (lengthInput.value > 0) || (widthInput.value > 0) || (heightInput.value > 0) || (serviceSelect.value !== ""));

        if (initialServiceId || isAnyRelevantFieldFilled) {
            if (initialServiceId) {
                 selectedServiceMainIdHidden.value = initialServiceId;
            }
            calculateAndUpdateCost(); // Recalcular con los datos iniciales o guardados
        } else {
            // Si no hay nada inicial, asegurar que el costo sea $0.00 y los displays estén ocultos
            costoEstimadoDisplay.value = "$0.00";
            costoEstimadoHidden.value = "0";
            if (selectedTransportDisplay) {
                selectedTransportDisplay.style.display = 'none';
            }
        }

        kmInput.addEventListener('input', calculateAndUpdateCost);
        weightInput.addEventListener('input', calculateAndUpdateCost);
        lengthInput.addEventListener('input', calculateAndUpdateCost);
        widthInput.addEventListener('input', calculateAndUpdateCost);
        heightInput.addEventListener('input', calculateAndUpdateCost);
        serviceSelect.addEventListener('change', calculateAndUpdateCost);

        newOrderForm.addEventListener('submit', (e) => {
            if (costoEstimadoDisplay.value.includes('N/A')) {
                alert("No se puede crear el pedido. Los valores de peso o dimensiones exceden los límites del servicio seleccionado.");
                e.preventDefault();
                return;
            }
            if (parseFloat(costoEstimadoHidden.value) <= 0 && serviceSelect.value !== "") {
                 alert("No se puede crear el pedido. El costo estimado es $0.00. Por favor, ajuste los valores.");
                 e.preventDefault();
                 return;
            }

        });
    });
</script>

<?php include 'includes/footer.php'; ?>