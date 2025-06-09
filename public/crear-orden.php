<?php
session_start();
include 'includes/header.php';

// Проверяем, что пользователь залогинен
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Получаем данные из URL
$service_id = htmlspecialchars($_GET['service_id'] ?? 'N/A');
$costo_estimado = htmlspecialchars($_GET['costo_estimado'] ?? '0.00');
?>
<div class="container" style="padding: 80px 0;">
    <h1>Crear Nuevo Pedido</h1>
    <p>Usted ha seleccionado el servicio con ID: <strong><?php echo $service_id; ?></strong></p>
    <p>El costo estimado es: <strong>$<?php echo $costo_estimado; ?></strong></p>
    <hr>
    <p>Aquí puede continuar llenando los detalles finales del pedido...</p>
    </div>
<?php include 'includes/footer.php'; ?>