<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}
include 'includes/header.php';
?>

<section class="personal-area-wrapper">

  <div class="personal-area">
    <h2>Área Personal</h2>
    <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_email']); ?>. Aquí podrás:</p>
    <ul>
      <li>Ver el estado de tus envíos</li>
      <li>Actualizar tus datos</li>
      <li>Historial de solicitudes</li>
    </ul>
    <p><a href="php_scripts/logout.php" class="access-link">Cerrar sesión</a></p>
  </div>

</section>

<?php include 'includes/footer.php'; ?>
