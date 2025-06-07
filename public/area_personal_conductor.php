<?php
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'conductor') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../server/database.php';

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
    echo '<div class="container mt-4"><div class="alert alert-danger">No se pudo encontrar el perfil de conductor asociado a su cuenta.</div></div>';
    include 'includes/footer.php';
    exit();
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Mis Envíos Actuales</h2>
    </div>

    <?php
    if (isset($_SESSION['update_status'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['update_status']) . '</div>';
        unset($_SESSION['update_status']);
    }
    ?>

    <?php
    // CORRECCIÓN FINAL: SQL-запрос полностью синхронизирован с вашей схемой
    $sql = "SELECT 
                e.envio_id, 
                e.lugar_origen, 
                e.lugar_distinto, 
                es.descripcion AS estado_actual
            FROM Envios e
            JOIN EstadoEnvio es ON e.EstadoEnvio_estado_envio_id1 = es.estado_envio_id
            JOIN Vehiculos v ON e.Vehiculos_vehiculos_id = v.vehiculos_id
            WHERE v.Conductores_conductor_id = ? AND es.descripcion NOT IN ('Finalizado', 'Cancelado')";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_conductor);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($envio = $result->fetch_assoc()) {
    ?>
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Envío #<?php echo htmlspecialchars($envio['envio_id']); ?></strong>
                </div>
                <div class="card-body">
                    <p class="card-text"><strong>Origen:</strong> <?php echo htmlspecialchars($envio['lugar_origen']); ?></p>
                    <p class="card-text"><strong>Destino:</strong> <?php echo htmlspecialchars($envio['lugar_distinto']); ?></p>
                    
                    <hr>

                    <form action="php_scripts/actualizar_estado_envio.php" method="POST" class="d-flex align-items-center">
                        <input type="hidden" name="id_envio" value="<?php echo $envio['envio_id']; ?>">
                        
                        <label for="estado-<?php echo $envio['envio_id']; ?>" class="form-label me-2">Estado:</label>
                        <select name="nuevo_estado" id="estado-<?php echo $envio['envio_id']; ?>" class="form-select me-3" style="width: 200px;">
                            <?php
                            // Эти значения должны совпадать с ENUM в вашей таблице EstadoEnvio
                            $estados_posibles = ['Pendiente', 'En proceso', 'Finalizado'];
                            foreach ($estados_posibles as $estado) {
                                $selected = ($envio['estado_actual'] == $estado) ? 'selected' : '';
                                echo "<option value=\"$estado\" $selected>$estado</option>";
                            }
                            ?>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </form>
                </div>
            </div>
    <?php
        }
    } else {
        echo '<div class="alert alert-secondary">No tienes envíos pendientes en este momento.</div>';
    }
    $stmt->close();
    $conn->close();
    ?>
</div>

<?php
include 'includes/footer.php'; 
?>