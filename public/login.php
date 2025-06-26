<?php
include 'includes/header.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    $redirect_page = 'index.php';
    switch ($role) {
        case 'administrador': $redirect_page = 'area_personal_admin.php'; break;
        case 'conductor': $redirect_page = 'area_personal_conductor.php'; break;
        case 'cliente': $redirect_page = 'area_personal_cliente.php'; break;
    }
    header("Location: " . $redirect_page);
    exit();
}

$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>

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

    <div class="registro-link-container">
        <p>
            ¿Aún no tienes una cuenta? 
            <a href="registro.php">Regístrate aquí</a>
        </p>
    </div>
    </main>

<script src="js/login.js"></script>
<?php include 'includes/footer.php'; ?>