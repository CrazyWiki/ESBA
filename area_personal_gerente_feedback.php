<?php
// Archivo: public/area_personal_gerente.php
// Propósito: Permite al Gerente de Ventas gestionar y ver todos los envíos.
session_start();

echo '<pre>';
print_r($_SESSION);
echo '</pre>';

include 'includes/header.php';
if (
    empty($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    trim(strtolower($_SESSION['user_role'])) !== 'gerente de ventas'
) {
    $_SESSION['update_status'] = 'Error: Acceso no autorizado para Gerente de ventas.';
    header("Location: ../login.php");
    exit();
}
require_once 'server/database.php';

$manager_user_id = $_SESSION['user_id'] ?? null;

// --- Obteniendo parámetros de filtro para Feedback ---
$filtro_feedback_fecha = $_GET['filtro_feedback_fecha'] ?? '';
$filtro_feedback_nombre_cliente = $_GET['filtro_feedback_nombre_cliente'] ?? '';
$filtro_feedback_email_cliente = $_GET['filtro_feedback_email_cliente'] ?? '';


// =====================================
// === HTML: Estructura de la página ===
// =====================================
?>

<section class="personal-area-container">
    <div class="content-wrapper">
        <div class="page-header">
            <h2>Gestión de Feedback de Clientes
                <a href="area_personal_gerente.php" class="btn btn-sm btn-outline-secondary ms-3" title="Volver a Gestión de Envíos">
                    <i class="fas fa-arrow-left"></i> Volver a Envíos
                </a>
            </h2>
        </div>

        <?php if (isset($_SESSION['update_status'])) : ?>
            <div class="user-message alert alert-info">
                <?php echo htmlspecialchars($_SESSION['update_status']); ?>
            </div>
            <?php unset($_SESSION['update_status']); ?>
        <?php endif; ?>

        <div class="filter-panel card p-4 mb-4">
            <h5 class="mb-3">Filtrar Mensajes de Feedback</h5>
            <form id="feedbackFilterForm" action="area_personal_gerente_feedback.php" method="GET" class="filter-form row g-3">
                <div class="col-md-4">
                    <label for="filtro_feedback_fecha" class="form-label">Fecha:</label>
                    <input type="date" id="filtro_feedback_fecha" name="filtro_feedback_fecha" class="form-control" value="<?php echo htmlspecialchars($filtro_feedback_fecha); ?>">
                </div>
                <div class="col-md-4">
                    <label for="filtro_feedback_nombre_cliente" class="form-label">Nombre Cliente:</label>
                    <input type="text" id="filtro_feedback_nombre_cliente" name="filtro_feedback_nombre_cliente" class="form-control" value="<?php echo htmlspecialchars($filtro_feedback_nombre_cliente); ?>" placeholder="ej: Juan Pérez">
                </div>
                <div class="col-md-4">
                    <label for="filtro_feedback_email_cliente" class="form-label">Email Cliente:</label>
                    <input type="email" id="filtro_feedback_email_cliente" name="filtro_feedback_email_cliente" class="form-control" value="<?php echo htmlspecialchars($filtro_feedback_email_cliente); ?>" placeholder="ej: cliente@email.com">
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar Feedback</button>
                    <a href="area_personal_gerente_feedback.php" class="btn btn-secondary">Limpiar Filtros</a>
                </div>
            </form>
        </div>

        <div class="feedback-section mt-4">
            <h3 class="mb-3">Mensajes de Clientes</h3>
            <div class="table-container">
                <table class="shipment-table" id="feedbackTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Cliente</th>
                            <th>Email Cliente</th>
                            <th>Fecha Mensaje</th>
                            <th>Mensaje</th>
                            <th>Comentario Interno</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="7" class="text-center">Cargando mensajes de feedback...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<div class="modal fade" id="addCommentModal" tabindex="-1" aria-labelledby="addCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCommentModalLabel">Añadir/Editar Comentario para Feedback #<span id="modalFeedbackId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p><strong>Mensaje del Cliente:</strong> <span id="clientMessageContent"></span></p>
                    <p><strong>De:</strong> <span id="clientMessageSender"></span></p>
                </div>
                <form id="commentForm">
                    <input type="hidden" id="commentFeedbackId" name="feedback_id">
                    <div class="mb-3">
                        <label for="employeeCommentText" class="form-label">Tu Comentario:</label>
                        <textarea class="form-control" id="employeeCommentText" name="employee_comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Comentario</button>
                    <div id="commentResponse" class="mt-2"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Variables inyectadas por PHP (contienen valores de filtro actuales)
    const MANAGER_USER_ID = <?php echo json_encode($manager_user_id); ?>;
    const FILTRO_FEEDBACK_FECHA = "<?php echo htmlspecialchars($filtro_feedback_fecha); ?>";
    const FILTRO_FEEDBACK_NOMBRE_CLIENTE = "<?php echo htmlspecialchars($filtro_feedback_nombre_cliente); ?>";
    const FILTRO_FEEDBACK_EMAIL_CLIENTE = "<?php echo htmlspecialchars($filtro_feedback_email_cliente); ?>";
</script>
<script src="js/area_personal_gerente_feedback.js"></script> 

<?php include 'includes/footer.php'; ?>