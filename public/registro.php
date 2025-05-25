<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header('Location: area_personal.php');
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<main class="container content-wrapper registro-container">
    <h1>Registro</h1>

    <form action="/php_scripts/registrar_cliente.php" method="POST" class="registro-form" id="registroCliente">
        <h2>Registro Cliente</h2>
        <label for="nombre_cliente">Nombre completo:</label>
        <input type="text" id="nombre_cliente" name="nombre" required />

        <label for="email_cliente">Correo electrónico:</label>
        <input type="email" id="email_cliente" name="email" required />

        <label for="usuario_cliente">Usuario:</label>
        <input type="text" id="usuario_cliente" name="usuario" required />

        <label for="password_cliente">Contraseña:</label>
        <input type="password" id="password_cliente" name="password" required />

        <button type="submit">Registrarse</button>
    </form>

    <form action="/php_scripts/registrar_empleado.php" method="POST" class="registro-form" id="registroEmpleado">
        <h2>Registro Empleado</h2>
        <label for="nombre_empleado">Nombre completo:</label>
        <input type="text" id="nombre_empleado" name="nombre" required />

        <label for="email_empleado">Correo electrónico:</label>
        <input type="email" id="email_empleado" name="email" required />

        <label for="usuario_empleado">Usuario:</label>
        <input type="text" id="usuario_empleado" name="usuario" required />

        <label for="password_empleado">Contraseña:</label>
        <input type="password" id="password_empleado" name="password" required />

        <button type="submit">Registrarse</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>
