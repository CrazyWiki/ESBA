<?php
// Archivo: public/area_personal_conductor.php
// Propósito: Muestra los envíos asignados a un conductor y permite actualizar su estado.

include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'conductor') {
    header("Location: login.php");
    exit();
}

require_once 'server/database.php';

$id_administrador = $_SESSION['user_id'];
$id_conductor = null;

$stmt_conductor = $conn->prepare("SELECT conductor_id FROM Conductores WHERE Administradores_idAdministradores = ?");
$stmt_conductor->bind_param("i", $id_administrador);
$stmt_conductor->execute();
$result_conductor = $stmt_conductor->get_result();

if ($conductor_row = $result_conductor->fetch_assoc()) {
    $id_conductor = $conductor_row['conductor_id'];
}
$stmt_conductor->close();

if ($id_conductor === null) {
    echo '<div class="personal-area-container"><div class="content-wrapper"><div class="alert alert-danger">No se pudo encontrar el perfil de conductor asociado a su cuenta.</div></div></div>';
    include 'includes/footer.php';
    exit();
}

$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_fecha = $_GET['filtro_fecha'] ?? '';

$sql = "SELECT e.envio_id, e.lugar_origen, e.lugar_distinto, e.fecha_envio, es.descripcion AS estado_actual
        FROM Envios e
        JOIN EstadoEnvio es ON e.EstadoEnvio_estado_envio_id1 = es.estado_envio_id
        JOIN Vehiculos v ON e.Vehiculos_vehiculos_id = v.vehiculos_id
        WHERE v.Conductores_conductor_id = ?";
$params = [$id_conductor];
$types = "i";

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

$sql .= " ORDER BY e.fecha_envio DESC";
?>

<section class="personal-area-container">
    <div class="content-wrapper">

        <div class="page-header">
            <h2>Mis Envíos</h2>
        </div>

        <div class="filter-panel">
            <h5>Filtrar Envíos</h5>
            <form action="area_personal_conductor.php" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="filtro_estado">Estado:</label>
                    <select id="filtro_estado" name="filtro_estado" class="form-select">
                        <option value="">Todos los Estados</option>
                        <?php
                        $estados_result = $conn->query("SELECT DISTINCT descripcion FROM EstadoEnvio ORDER BY descripcion");
                        while ($estado_row = $estados_result->fetch_assoc()) {
                            $estado = htmlspecialchars($estado_row['descripcion']);
                            $selected = ($filtro_estado == $estado) ? 'selected' : '';
                            echo "<option value=\"$estado\" $selected>$estado</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filtro_fecha">Fecha:</label>
                    <input type="date" id="filtro_fecha" name="filtro_fecha" class="form-control" value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-custom btn-custom-primary">Filtrar</button>
                </div>
                <div class="filter-group">
                    <a href="area_personal_conductor.php" class="btn-custom btn-custom-secondary">Limpiar</a>
                </div>
            </form>
        </div>

        <?php
        if (isset($_SESSION['update_status'])) {
            echo '<div class="user-message">' . htmlspecialchars($_SESSION['update_status']) . '</div>';
            unset($_SESSION['update_status']);
        }

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        ?>
            <table class="shipment-table">
                <thead>
                    <tr>
                        <th>Envío #</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Fecha</th>
                        <th>Estado Actual</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0) : ?>
                        <?php while ($envio = $result->fetch_assoc()) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($envio['envio_id']); ?></td>
                                <td><?php echo htmlspecialchars($envio['lugar_origen']); ?></td>
                                <td><?php echo htmlspecialchars($envio['lugar_distinto']); ?></td>
                                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($envio['fecha_envio']))); ?></td>
                                <td>
                                    <?php
                                    $status_class = 'status-' . strtolower(str_replace(' ', '-', $envio['estado_actual']));
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($envio['estado_actual']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form action="php_scripts/actualizar_estado_envio.php" method="POST" class="update-form">
                                        <input type="hidden" name="id_envio" value="<?php echo $envio['envio_id']; ?>">
                                        <select name="nuevo_estado" class="form-select">
                                            <?php
                                            $estados_posibles = ['Pendiente', 'En proceso', 'Finalizado', 'Cancelado'];
                                            foreach ($estados_posibles as $estado) {
                                                $selected = ($envio['estado_actual'] == $estado) ? 'selected' : '';
                                                echo "<option value=\"$estado\" $selected>$estado</option>";
                                            }
                                            ?>
                                        </select>
                                        <button type="submit" class="btn-custom btn-custom-primary">Actualizar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" class="no-results">No se encontraron envíos con los filtros seleccionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php
            $stmt->close();
        } else {
            echo '<div class="user-message error-message">Error al preparar la consulta.</div>';
        }
        $conn->close();
        ?>
    </div>
</section>

<?php
include 'includes/footer.php';
?>
