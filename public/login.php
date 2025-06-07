<?php
// La sesión ya NO se inicia aquí. Se inicia de forma segura dentro de 'header.php'.
// La lógica de redirección se basa en la sesión que 'header.php' ya inició.

// Si un ADMINISTRADOR ya ha iniciado sesión, redirigir al panel de administración.
if (isset($_SESSION['admin_id'])) {
    header("Location: area_personal_admin.php");
    exit();
}

// Si un EMPLEADO normal ya ha iniciado sesión, redirigir a su área personal.
if (isset($_SESSION['empleado_id']) && !isset($_SESSION['admin_id'])) { 
    header("Location: area_empleado.php"); // REEMPLAZA 'area_empleado.php' con la página real para empleados
    exit();
}

// Obtener el mensaje de error de inicio de sesión, si existe, desde la sesión.
$login_error = $_SESSION['login_error'] ?? '';
// Limpiar el mensaje de la sesión después de mostrarlo para que no aparezca de nuevo.
unset($_SESSION['login_error']); 
?>
<?php include 'includes/header.php'; // Tu header existente, que ahora maneja session_start() ?>

<main class="registro">
    <h2>Login</h2>

    <?php if (!empty($login_error)): ?>
        <p class="login-error-message">
            <?php echo htmlspecialchars($login_error); ?>
        </p>
    <?php endif; ?>

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
        <h3>Login para Empleado / Personal</h3> 
        <form action="php_scripts/login_empleado.php" method="post">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>

</main>

<script src="js/login.js"></script>
<?php include 'includes/footer.php'; // Tu footer existente ?>