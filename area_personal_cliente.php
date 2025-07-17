<?php
// Archivo: public/area_personal_cliente.php
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
// --- Lógica para obtener datos de servicios y calculadora (sin cambios) ---
$servicios_calculadora_data = [];
$vehiculos_capacidades = []; 

try {
    $sqlVehiculos = "SELECT tipo, capacidad_kg, capacidad_m3 FROM Vehiculos";
    $resultVehiculos = $conn->query($sqlVehiculos);
    while ($rowVehiculo = $resultVehiculos->fetch_assoc()) {
        $vehiculos_capacidades[strtolower($rowVehiculo['tipo'])] = [
            'max_peso_kg' => floatval($rowVehiculo['capacidad_kg']),
            'max_volumen_m3' => floatval($rowVehiculo['capacidad_m3'])
        ];
    }
    
    $sql = "
        SELECT s.servicio_id, s.nombre_servicio, s.descripcion, s.unidad_medida_tarifa, t.tarifa_id, t.precio, t.factor_multiplicador
        FROM Servicios s
        LEFT JOIN Tarifas t ON s.servicio_id = t.Servicios_servicio_id
        WHERE t.fecha_vigencia_inicio <= CURDATE() AND (t.fecha_vigencia_fin IS NULL OR t.fecha_vigencia_fin >= CURDATE())
        ORDER BY s.nombre_servicio, s.unidad_medida_tarifa";
    $result_raw_services = $conn->query($sql);
    $rawServicesData = [];
    while ($row = $result_raw_services->fetch_assoc()) {
        $rawServicesData[] = $row;
    }
    $groupedLogicalServices = [];
    foreach ($rawServicesData as $row) {
        $commonServiceName = trim(preg_replace('/\s*\((?:Por (?:KM|KG|Hora)|Base)\)\s*/i', '', $row['nombre_servicio']));
        $commonServiceKey = strtolower($commonServiceName);

        if (!isset($groupedLogicalServices[$commonServiceKey])) {
            $groupedLogicalServices[$commonServiceKey] = [
                'main_servicio_id' => null, 'nombre_servicio' => $commonServiceName, 'descripcion' => $row['descripcion'],
                'capacidades' => ['max_peso_kg' => null, 'max_volumen_m3' => null],
                'costo_base' => 0, 'costo_por_km' => 0, 'costo_por_kg' => 0, 'icon_class' => 'fas fa-box'
            ];
            if (strtolower($row['unidad_medida_tarifa']) === 'base') {
                $groupedLogicalServices[$commonServiceKey]['main_servicio_id'] = $row['servicio_id'];
            } else {
                if ($groupedLogicalServices[$commonServiceKey]['main_servicio_id'] === null) {
                    $groupedLogicalServices[$commonServiceKey]['main_servicio_id'] = $row['servicio_id'];
                }
            }
            $currentServiceType = '';
            if (str_contains($commonServiceKey, 'motocicleta')) $currentServiceType = 'motocicleta';
            elseif (str_contains($commonServiceKey, 'furgoneta') || str_contains($commonServiceKey, 'furgón')) $currentServiceType = 'furgoneta';
            elseif (str_contains($commonServiceKey, 'camión') || str_contains($commonServiceKey, 'camion')) $currentServiceType = 'camión';
            elseif (str_contains($commonServiceKey, 'pickup')) $currentServiceType = 'pickup';

            if (!empty($currentServiceType) && isset($vehiculos_capacidades[$currentServiceType])) {
                $groupedLogicalServices[$commonServiceKey]['capacidades'] = $vehiculos_capacidades[$currentServiceType];
            }
            $groupedLogicalServices[$commonServiceKey]['icon_class'] = match($currentServiceType) {
                'motocicleta' => 'fas fa-motorcycle',
                'furgoneta', 'pickup' => 'fas fa-truck-pickup',
                'camión' => 'fas fa-truck',
                default => 'fas fa-box'
            };
        }
        $costo_calculado = floatval($row['precio'] ?: 0) * floatval($row['factor_multiplicador'] ?: 1);
        $unidad = strtolower($row['unidad_medida_tarifa']);
        if ($unidad === 'base') $groupedLogicalServices[$commonServiceKey]['costo_base'] = $costo_calculado;
        elseif ($unidad === 'km') $groupedLogicalServices[$commonServiceKey]['costo_por_km'] = $costo_calculado;
        elseif ($unidad === 'kg') $groupedLogicalServices[$commonServiceKey]['costo_por_kg'] = $costo_calculado;
    }
    $servicios_calculadora_data = array_values($groupedLogicalServices);

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
?>

<section class="personal-area-container">
    <div class="content-wrapper">
        <div class="page-header">
            <h2>Mi Área Personal</h2>
        </div>

        <?php
        if (isset($_SESSION['order_status_msg'])) {
            echo '<div class="user-message alert alert-info">' . htmlspecialchars($_SESSION['order_status_msg']) . '</div>';
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
                    <input type="text" id="costo_estimado_display" class="form-control" value="$0.00" readonly style="background-color: #e9ecef; font-weight: bold;">
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
                        <label for="fecha_envio">Fecha de Recogida</label>
                        <input type="date" id="fecha_envio" name="fecha_envio" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Lógica para mostrar historial de pedidos (sin cambios)
                    $sql_historial = "SELECT e.envio_id, e.fecha_envio, e.lugar_origen, e.lugar_distinto, es.descripcion AS estado, de.descripcion AS detalles_paquete_json, de.peso_kg AS peso_detalle, de.alto_cm AS alto_detalle, de.largo_cm AS largo_detalle, de.ancho_cm AS ancho_detalle, de.km AS km_detalle, de.Servicios_servicio_id AS servicio_id_detalle FROM Envios e JOIN EstadoEnvio es ON e.EstadoEnvio_estado_envio_id1 = es.estado_envio_id LEFT JOIN DetalleEnvio de ON e.envio_id = de.Envios_envio_id WHERE e.Clientes_id_cliente = ? ORDER BY e.fecha_envio DESC";
                    $stmt_historial = $conn->prepare($sql_historial);
                    $stmt_historial->bind_param("i", $id_cliente);
                    $stmt_historial->execute();
                    $result_historial = $stmt_historial->get_result();

                    if ($result_historial->num_rows > 0):
                        while ($envio = $result_historial->fetch_assoc()):
                            // ... Lógica de parseo de datos del envío (sin cambios) ...
                            $costo_recuperado = 'N/A';
                            $desc_usuario_recuperada = 'Sin descripción adicional.';
                            $detalle_parsed = json_decode($envio['detalles_paquete_json'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($detalle_parsed)) {
                                $costo_recuperado = isset($detalle_parsed['costo_estimado_final']) ? number_format($detalle_parsed['costo_estimado_final'], 2, '.', ',') : 'N/A';
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
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($envio['estado']); ?></span>
                                </td>
                                <td>$<?php echo $costo_recuperado; ?></td>
                                <td>
                                    <strong>KM:</strong> <?php echo htmlspecialchars($envio['km_detalle'] ?? 'N/A'); ?><br>
                                    <strong>Peso:</strong> <?php echo htmlspecialchars($envio['peso_detalle'] ?? 'N/A'); ?> kg<br>
                                    <strong>Dimensiones:</strong> <?php echo htmlspecialchars($envio['largo_detalle'] ?? 'N/A'); ?>x<?php echo htmlspecialchars($envio['ancho_detalle'] ?? 'N/A'); ?>x<?php echo htmlspecialchars($envio['alto_detalle'] ?? 'N/A'); ?> cm<br>
                                    <?php if (!empty($desc_usuario_recuperada) && $desc_usuario_recuperada !== 'Sin descripción adicional.'): ?>
                                        <strong>Descripción:</strong> <?php echo htmlspecialchars($desc_usuario_recuperada); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($envio['estado'] === 'Pendiente'): ?>
                                        <button class="btn btn-danger btn-sm delete-order-btn" data-envio-id="<?php echo $envio['envio_id']; ?>">Eliminar</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">Aún no has realizado ningún pedido.</td>
                        </tr>
                    <?php
                    endif;
                    $stmt_historial->close();
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
    const SERVICES_CALCULATOR_DATA = <?php echo json_encode($servicios_calculadora_data ?? []); ?>;
    const initialServiceIdFromUrl = "<?php echo isset($_GET['service_id']) ? htmlspecialchars($_GET['service_id']) : ''; ?>";
    const CLEAR_CALCULATOR_DATA_FLAG = <?php echo (isset($_SESSION['clear_calculator_data_flag']) && $_SESSION['clear_calculator_data_flag']) ? 'true' : 'false'; ?>;
    <?php 
        if (isset($_SESSION['clear_calculator_data_flag'])) {
            unset($_SESSION['clear_calculator_data_flag']);
        }
    ?>

    document.addEventListener('DOMContentLoaded', function() {
        const newOrderForm = document.getElementById('newOrderForm');
        const kmInput = document.getElementById('km');
        const weightInput = document.getElementById('weight');
        const lengthInput = document.getElementById('length');
        const widthInput = document.getElementById('width');
        const heightInput = document.getElementById('height');
        const serviceSelect = document.getElementById('id_servicio');
        const fechaEnvioInput = document.getElementById('fecha_envio');
        const origenInput = document.getElementById('lugar_origen');
        const destinoInput = document.getElementById('lugar_destino');
        const costoEstimadoDisplay = document.getElementById('costo_estimado_display');
        const costoEstimadoHidden = document.getElementById('costo_estimado_hidden');
        const selectedServiceMainIdHidden = document.getElementById('selected_service_main_id_hidden');
        const selectedTransportDisplay = document.getElementById('selected_transport_display');
        const selectedTransportIcon = document.getElementById('selected_transport_icon');
        const selectedTransportName = document.getElementById('selected_transport_name');
        const submitButton = newOrderForm.querySelector('button[type="submit"]');
        
        const availabilityMessageDiv = document.createElement('div');
        availabilityMessageDiv.className = 'alert mt-2';
        const formParent = newOrderForm.querySelector('.form-group.mt-4');
        if (formParent) {
            newOrderForm.insertBefore(availabilityMessageDiv, formParent);
        }

        if (CLEAR_CALCULATOR_DATA_FLAG) {
            sessionStorage.removeItem('calculatorData');
        }
        
        const savedDataJSON = sessionStorage.getItem('calculatorData');
        if (savedDataJSON) {
            try {
                const savedData = JSON.parse(savedDataJSON);
                
                if(initialServiceIdFromUrl) {
                    savedData.id_servicio = initialServiceIdFromUrl;
                }

                kmInput.value = savedData.km || '';
                weightInput.value = savedData.weight || '';
                lengthInput.value = savedData.length || '';
                widthInput.value = savedData.width || '';
                heightInput.value = savedData.height || '';
                serviceSelect.value = savedData.id_servicio || '';
                origenInput.value = savedData.lugar_origen || '';
                destinoInput.value = savedData.lugar_destino || '';
                
                sessionStorage.setItem('calculatorData', JSON.stringify(savedData));
            } catch (e) {
                console.error("Error al cargar datos de sessionStorage:", e);
                sessionStorage.removeItem('calculatorData');
            }
        }

        function saveCurrentFormData() {
            const currentData = {
                km: kmInput.value,
                weight: weightInput.value,
                length: lengthInput.value,
                width: widthInput.value,
                height: heightInput.value,
                id_servicio: serviceSelect.value,
                lugar_origen: origenInput.value,
                lugar_destino: destinoInput.value
            };
            sessionStorage.setItem('calculatorData', JSON.stringify(currentData));
        }

        const formInputsToSave = [kmInput, weightInput, lengthInput, widthInput, heightInput, serviceSelect, fechaEnvioInput, origenInput, destinoInput];
        formInputsToSave.forEach(input => {
            if(input) input.addEventListener('input', saveCurrentFormData);
        });

        const checkVehicleAvailability = () => {
            const currentFechaEnvio = fechaEnvioInput.value;
            const currentKm = parseFloat(kmInput.value) || 0;

            if (!currentFechaEnvio || currentKm <= 0) {
                availabilityMessageDiv.className = 'alert alert-info mt-2';
                availabilityMessageDiv.innerHTML = 'Ingrese la distancia (KM) y seleccione una fecha para verificar la disponibilidad.';
                submitButton.disabled = true;
                updateServiceOptionsAvailability([]);
                return;
            }

            submitButton.disabled = true;
            availabilityMessageDiv.className = 'alert alert-info mt-2';
            availabilityMessageDiv.innerHTML = 'Verificando disponibilidad...';
            const queryParams = new URLSearchParams({ fecha: currentFechaEnvio, km: currentKm }).toString();

            fetch(`php_scripts/get_available_services_for_date.php?${queryParams}`)
                .then(response => response.ok ? response.json() : Promise.reject('Error de red'))
                .then(data => {
                    updateServiceOptionsAvailability(data.available_services_ids);
                    validateCurrentSelection();
                })
                .catch(error => {
                    availabilityMessageDiv.className = 'alert alert-danger mt-2';
                    availabilityMessageDiv.innerHTML = 'Error al verificar la disponibilidad.';
                });
        };

        const updateServiceOptionsAvailability = (availableServiceIds) => {
            Array.from(serviceSelect.options).forEach(option => {
                if (!option.value) return;
                const serviceId = parseInt(option.value);
                const isAvailable = Array.isArray(availableServiceIds) && availableServiceIds.includes(serviceId);
                option.disabled = !isAvailable;
                option.style.color = isAvailable ? '' : '#999';
            });
        };

        const validateCurrentSelection = () => {
            const currentServiceId = serviceSelect.value;
            const currentCost = parseFloat(costoEstimadoHidden.value) || 0;
            const isCurrentSelectionDisabled = serviceSelect.options[serviceSelect.selectedIndex]?.disabled;

            if (currentServiceId && !isCurrentSelectionDisabled && currentCost > 0) {
                availabilityMessageDiv.className = 'alert alert-success mt-2';
                availabilityMessageDiv.innerHTML = "Transporte disponible para esta fecha y distancia.";
                submitButton.disabled = false;
            } else if (currentServiceId && isCurrentSelectionDisabled) {
                availabilityMessageDiv.className = 'alert alert-danger mt-2';
                availabilityMessageDiv.innerHTML = "El tipo de transporte seleccionado no está disponible.";
                submitButton.disabled = true;
            } else {
                 availabilityMessageDiv.className = 'alert alert-warning mt-2';
                 availabilityMessageDiv.innerHTML = "Por favor, seleccione un tipo de envío disponible.";
                 submitButton.disabled = true;
            }
        };

        const calculateAndUpdateCost = () => {
            const currentKm = parseFloat(kmInput.value) || 0;
            const currentWeight = parseFloat(weightInput.value) || 0;
            const currentLength = parseFloat(lengthInput.value) || 0;
            const currentWidth = parseFloat(widthInput.value) || 0;
            const currentHeight = parseFloat(heightInput.value) || 0;
            const currentServiceId = serviceSelect.value;

            submitButton.disabled = true;
            selectedTransportDisplay.style.display = 'none';

            if (!currentServiceId) {
                costoEstimadoDisplay.value = "$0.00";
                costoEstimadoHidden.value = "0";
                checkVehicleAvailability();
                return;
            }

            const selectedService = SERVICES_CALCULATOR_DATA.find(s => s.main_servicio_id == currentServiceId);

            if (!selectedService || currentKm <= 0 || currentWeight <= 0) {
                 costoEstimadoDisplay.value = "$0.00";
                 costoEstimadoHidden.value = "0";
                 checkVehicleAvailability();
                 return;
            }

            const currentVolumeM3 = (currentLength * currentWidth * currentHeight) / 1000000;
            const maxPeso = selectedService.capacidades?.max_peso_kg;
            const maxVolumen = selectedService.capacidades?.max_volumen_m3;

            if ((maxPeso && currentWeight > maxPeso) || (maxVolumen && currentVolumeM3 > maxVolumen)) {
                costoEstimadoDisplay.value = 'N/A';
                costoEstimadoDisplay.style.color = 'red';
                costoEstimadoHidden.value = '';
                selectedTransportDisplay.style.display = 'block';
                selectedTransportIcon.className = 'fas fa-exclamation-triangle fa-2x me-3 text-danger';
                selectedTransportName.textContent = selectedService.nombre_servicio + " (Límites excedidos)";
                return;
            }

            costoEstimadoDisplay.style.color = 'inherit';
            const totalCost = (selectedService.costo_base || 0) + (currentKm * (selectedService.costo_por_km || 0)) + (currentWeight * (selectedService.costo_por_kg || 0));
            costoEstimadoDisplay.value = `$${totalCost.toFixed(2)}`;
            costoEstimadoHidden.value = totalCost.toFixed(2);
            selectedServiceMainIdHidden.value = currentServiceId;
            
            selectedTransportDisplay.style.display = 'block';
            selectedTransportIcon.className = (selectedService.icon_class || 'fas fa-box') + ' fa-2x me-3';
            selectedTransportName.textContent = selectedService.nombre_servicio;

            checkVehicleAvailability();
        };

        formInputsToSave.forEach(input => {
            if (input) input.addEventListener('input', calculateAndUpdateCost);
        });
        
        if (kmInput.value > 0 || weightInput.value > 0) {
            calculateAndUpdateCost();
        } else {
            availabilityMessageDiv.className = 'alert alert-info mt-2';
            availabilityMessageDiv.innerHTML = 'Complete los datos y seleccione una fecha para continuar.';
            submitButton.disabled = true;
        }

        document.querySelectorAll('.delete-order-btn').forEach(button => {
            button.addEventListener('click', function() {
                const envioId = this.dataset.envioId;
                if (confirm(`¿Estás seguro de que quieres eliminar el pedido #${envioId}?`)) {
                    fetch('php_scripts/delete_order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ envio_id: envioId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>