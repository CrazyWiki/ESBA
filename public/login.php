<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    // Si ya está logueado, redirigir al área personal
    header('Location: area_personal.php');
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<main class="container content-wrapper login-container">
    <h1>Iniciar sesión</h1>

    <form action="/php_scripts/login_cliente.php" method="POST" class="login-form" id="formCliente">
        <h2>Cliente</h2>
        <label for="usuario_cliente">Usuario o email:</label>
        <input type="text" id="usuario_cliente" name="usuario" required />

        <label for="password_cliente">Contraseña:</label>
        <input type="password" id="password_cliente" name="password" required />

        <button type="submit">Ingresar</button>
    </form>

    <form action="/php_scripts/login_empleado.php" method="POST" class="login-form" id="formEmpleado">
        <h2>Empleado</h2>
        <label for="usuario_empleado">Usuario o email:</label>
        <input type="text" id="usuario_empleado" name="usuario" required />

        <label for="password_empleado">Contraseña:</label>
        <input type="password" id="password_empleado" name="password" required />

        <button type="submit">Ingresar</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>
