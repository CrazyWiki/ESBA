<?php
include 'includes/header.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<section class="admin-panel-container">
    <div class="content-wrapper">
        <div class="page-header">
            <h2>Panel de Control de Tablas</h2>
        </div>

        <div class="admin-controls">
            <label for="tableSelector">Seleccione una tabla para administrar:</label>
            <select id="tableSelector">
                <option value="">Cargando lista de tablas...</option>
            </select>
        </div>

        <div id="adminUserMessageArea" class="user-message"></div>

        <div class="admin-actions-bar">
            <button id="adminAddNewRowBtn" class="cta-button admin-hidden">Añadir nueva fila</button>
        </div>

        <div id="adminDynamicTableContainer" class="admin-table-container">
            <p class="admin-info-text">Por favor, seleccione una tabla del menú desplegable.</p>
        </div>
    </div>
</section>

<script src="js/admin_panel_app.js"></script>

<?php
include 'includes/footer.php';
?>
