<?php
// Archivo: public/calculadora.php
// Propósito: Permite a los usuarios estimar el costo de sus envíos y seleccionar un servicio.

// Inicia la sesión PHP si aún no está iniciada.
session_start();
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<section class="personal-area-container">
    <div class="container">
        <div class="page-header">
            <h2>Calculadora de Costos de Envío</h2>
        </div>
        <p class="lead text-center mb-5">
            Complete los detalles de su envío para ver las opciones disponibles y sus costos estimados.
        </p>
        <div class="panel">
            <h3>Estime el costo de su envío</h3>
            <form id="calculatorForm" class="calculator-form">
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
                    <div class="col-md-6 form-group">
                        <label for="km">Distancia (km)</label>
                        <input type="number" id="km" name="km" class="form-control" placeholder="Ej: 25" required step="0.1" min="0">
                    </div>
                    <div class="col-md-6 form-group">
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
                <div class="form-group mt-4">
                    <button type="button" id="calculateBtn" class="btn btn-primary">Ver Opciones de Envío</button>
                </div>
            </form>
        </div>

        <div id="calculationResult" class="mt-5"></div>
    </div>
</section>

<script>
    const IS_USER_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script>

<script src="js/calculator_precio.js"></script>

<?php include 'includes/footer.php'; ?>
