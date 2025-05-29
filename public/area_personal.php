<?php include 'includes/header.php'; ?>
<form action="php_scripts/logout.php" method="post">
    <button type="submit">Cerrar sesión</button>
</form>
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Contenido de la zona privada:
echo "<h1>Bienvenido, " . htmlspecialchars($_SESSION['usuario_email']) . "!</h1>";
?>
<?php include 'includes/footer.php'; ?>