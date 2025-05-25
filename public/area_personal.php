<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<main class="container content-wrapper area-personal">
    <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></h1>
    <p>Esta es tu área personal.</p>

    <a href="/php_scripts/logout.php" class="btn-logout">Cerrar sesión</a>
</main>

<?php include 'includes/footer.php'; ?>
