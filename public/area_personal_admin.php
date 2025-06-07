<?php
// Incluimos el header. Él se encarga de iniciar la sesión y mostrar el menú correcto.
include 'includes/header.php';

// Verificación de seguridad: si no es un admin, lo sacamos de aquí.
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirigir a la página de login
    exit();
}
?>

<div class="admin-panel-container">
    <div class="admin-panel-header">
        <h2>Panel de Control de Tablas</h2>
        </div>

    <div class="admin-controls-area">
        <label for="tableSelector">Seleccione una tabla para administrar:</label>
        <select id="tableSelector">
            <option value="">Cargando lista de tablas...</option>
        </select>
    </div>

    <div id="adminUserMessageArea"></div>

    <div class="admin-table-actions-bar">
        <button id="adminAddNewRowBtn" class="admin-action-button admin-hidden">Añadir nueva fila</button>
    </div>

    <div id="adminDynamicTableContainer">
        <p class="admin-info-text">Por favor, seleccione una tabla del menú desplegable.</p>
    </div>
</div>

<script src="js/admin_panel_app.js"></script>

<?php
// Incluimos el footer para cerrar la página.
include 'includes/footer.php'; 
?>