<?php 
// Es buena práctica iniciar la sesión al principio si vas a mostrar mensajes de error/éxito
session_start();
include 'includes/header.php'; 
?>

<main class="registro">
    <h2>Registro</h2>

    <?php 
    if (isset($_SESSION['form_errors'])) {
        echo '<div class="alert alert-danger">';
        foreach ($_SESSION['form_errors'] as $error) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
        }
        echo '</div>';
        unset($_SESSION['form_errors']);
    }
    ?>

    <div id="formCliente" class="contact-form">
        <h3>Registro de Cliente</h3>
        <form method="post">
            <input type="hidden" name="tipo" value="cliente">
            <input type="text" name="nombre_cliente" placeholder="Nombre" required>
            <input type="text" name="apellido_cliente" placeholder="Apellido" required>
            <input type="text" name="telefono" placeholder="Número de teléfono">
            <input type="text" name="direccion" placeholder="Dirección">
            <input type="text" name="codigo_postal" placeholder="Código Postal">
            <input type="text" name="numero_documento" placeholder="Documento de Identidad" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required minlength="8">
            <button type="submit">Registrarse</button>
        </form>
    </div>
</main>

<script src="/ESBA/public/js/registro.js" defer></script>

<?php include 'includes/footer.php'; ?>