<?php include 'includes/header.php'; ?>
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Contenido de la zona privada:
echo "<h1>Bienvenido, " . htmlspecialchars($_SESSION['usuario_email']) . "!</h1>";
?>
<main class="registro">
    <h2>Login</h2>

    <div class="login-form">
        <button onclick="mostrarFormulario('cliente')">Login como Cliente</button>
        <button onclick="mostrarFormulario('empleado')">Login como Empleado</button>
    </div>

    <div id="formClientelogin" class="login-form">
        <h3>Login para Cliente</h3>
        <form action="php_scripts/login_cliente.php" method="post">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>

    <div id="formEmpleadologin" class="login-form" style="display: none;">
        <h3>Login para Empleado</h3>
        <form action="procesar_registro.php" method="post">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="button">Ingresar</button>
        </form>
    </div>
</main>


<script src="js/login.js"></script>
<?php include 'includes/footer.php'; ?>