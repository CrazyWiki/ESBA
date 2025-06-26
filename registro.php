<?php 
// Archivo: public/registro.php
// Propósito: Muestra el formulario de registro para nuevos clientes.

// Inicia la sesión PHP si aún no está iniciada.
session_start();
include 'includes/header.php'; 
?>

<main class="registro">
    <h2>Registro de Cliente</h2>
    
    <div id="formCliente" class="contact-form">
        <form action="php_scripts/registrar_cliente.php" method="post">
            <input type="hidden" name="tipo" value="cliente">
            <input type="text" name="nombre_cliente" placeholder="Nombre" required>
            <input type="text" name="apellido_cliente" placeholder="Apellido" required>
            <input type="text" name="telefono" placeholder="Número de teléfono">
            <input type="text" name="direccion" placeholder="Dirección">
            <input type="text" name="codigo_postal" placeholder="Código Postal">
            <input type="text" name="numero_documento" placeholder="Documento de Identidad" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña (mínimo 8 caracteres)" required minlength="8">
            <button type="submit">Registrarse</button>
        </form>
    </div>
</main>

<script src="js/registro.js" defer></script>

<?php include 'includes/footer.php'; ?>
