<?php
// session_start() должен быть в header.php
include 'includes/header.php';

// Перенаправляем, если пользователь НЕ залогинен ИЛИ его роль НЕ 'cliente'
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'cliente')) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../server/database.php';

// --- Получаем ID клиента ---
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

// Если ID клиента не найден, прерываем выполнение
if ($id_cliente === null) {
    echo '<section class="content-wrapper"><div class="alert alert-danger">No se pudo encontrar el perfil de cliente asociado a su cuenta.</div></section>';
    include 'includes/footer.php';
    exit();
}

// --- Получаем список всех доступных услуг для выпадающего меню ---
$servicios = [];
// *** ИСПРАВЛЕНО ***: Используем правильные имена столбцов 'servicio_id' и 'nombre_servicio'
$result_servicios = $conn->query("SELECT servicio_id, nombre_servicio FROM Servicios ORDER BY nombre_servicio");
if ($result_servicios) {
    while ($row = $result_servicios->fetch_assoc()) {
        $servicios[] = $row;
    }
}
?>

<section class="personal-area-container">
    <div class="content-wrapper">
        <div class="page-header">
            <h2>Mi Área Personal</h2>
        </div>

        <?php
        // Сообщение об успешном создании заказа
        if (isset($_SESSION['order_status_msg'])) {
            echo '<div class="user-message alert alert-success">' . htmlspecialchars($_SESSION['order_status_msg']) . '</div>';
            unset($_SESSION['order_status_msg']);
        }
        ?>

        <div class="panel">
            <h3>Realizar un Nuevo Pedido</h3>
            <form id="newOrderForm" action="php_scripts/procesar_nuevo_envio.php" method="POST" class="order-form">
                <input type="hidden" id="costo_estimado" name="costo_estimado">

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
                </div>

                <h4 class="mt-4">Dimensiones del Paquete (cm)</h4>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="length">Largo</label>
                        <input type="number" id="length" name="length" class="form-control" placeholder="Ej: 50" required min="0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="width">Ancho</label>
                        <input type="number" id="width" name="width" class="form-control" placeholder="Ej: 40" required min="0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="height">Alto</label>
                        <input type="number" id="height" name="height" class="form-control" placeholder="Ej: 30" required min="0">
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="id_servicio">Tipo de Envío</label>
                        <select id="id_servicio" name="id_servicio" class="form-control" required>
                            <option value="">-- Seleccione un servicio --</option>
                            <?php foreach ($servicios as $servicio): ?>
                                <option value="<?php echo htmlspecialchars($servicio['servicio_id']); ?>">
                                    <?php echo htmlspecialchars($servicio['nombre_servicio']); ?>
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
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // --- ПОЛНАЯ ИСПРАВЛЕННАЯ ЛОГИКА ВЫВОДА ИСТОРИИ ---
                    $sql = "SELECT e.envio_id, e.fecha_envio, e.lugar_origen, e.lugar_distinto, es.descripcion AS estado
                            FROM Envios e
                            JOIN estadoenvio es ON e.EstadoEnvio_estado_envio_id1 = es.estado_envio_id
                            WHERE e.Clientes_id_cliente = ?
                            ORDER BY e.fecha_envio DESC";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id_cliente);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0):
                        while ($envio = $result->fetch_assoc()):
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
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aún no has realizado ningún pedido.</td>
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
document.addEventListener('DOMContentLoaded', function() {
    const savedDataJSON = sessionStorage.getItem('calculatorData');
    if (savedDataJSON) {
        try {
            const savedData = JSON.parse(savedDataJSON);
            console.log('Найдены данные из калькулятора:', savedData);

            document.getElementById('lugar_origen').value = savedData.lugar_origen || '';
            document.getElementById('lugar_destino').value = savedData.lugar_destino || '';
            document.getElementById('km').value = savedData.km || '';
            document.getElementById('weight').value = savedData.weight || '';
            document.getElementById('length').value = savedData.length || '';
            document.getElementById('width').value = savedData.width || '';
            document.getElementById('height').value = savedData.height || '';

            sessionStorage.removeItem('calculatorData');
            console.log('Данные калькулятора удалены.');
        } catch (e) {
            console.error('Ошибка парсинга данных из sessionStorage:', e);
            sessionStorage.removeItem('calculatorData');
        }
    }

    const urlParams = new URLSearchParams(window.location.search);
    const serviceId = urlParams.get('service_id');
    const costoEstimado = urlParams.get('costo_estimado');

    if (serviceId) {
        const serviceSelect = document.getElementById('id_servicio');
        if (serviceSelect) {
            serviceSelect.value = serviceId;
            console.log(`Установлен ID услуги в выпадающем списке: ${serviceId}`);
        }
    }
    if (costoEstimado) {
        document.getElementById('costo_estimado').value = costoEstimado;
        const costElement = document.createElement('p');
        costElement.style.marginBottom = '1rem';
        costElement.innerHTML = `Costo estimado para este envío: <strong>$${costoEstimado}</strong> (basado en su cálculo previo).`;
        const form = document.getElementById('newOrderForm');
        form.prepend(costElement);
    }
});
</script>

<?php include 'includes/footer.php'; ?>