<?php include 'includes/header.php'; ?>

<main class="registro">
  <h2>Registro</h2>

  <div class="contact-form">
    <button onclick="mostrarFormulario('cliente')">Registrarse como Cliente</button>
    <button onclick="mostrarFormulario('empleado')">Registrarse como Empleado</button>
  </div>

  <div id="formCliente" class="contact-form">
    <h3>Registro de Cliente</h3>
    <form action="php_scripts/registrar_cliente.php" method="post">
      <input type="hidden" name="tipo" value="cliente">
      <input type="text" name="nombre_cliente" placeholder="Nombre" required>
      <input type="text" name="apellido_cliente" placeholder="Apellido" required>
      <input type="text" name="telefono" placeholder="Número de teléfono">
      <input type="text" name="direccion" placeholder="Dirección">
      <input type="text" name="codigo_postal" placeholder="Código Postal">
      <input type="text" name="numero_documento" placeholder="Documento de Identidad" required>

      <!-- Datos del usuario relacionado -->
      <input type="email" name="email" placeholder="Correo electrónico" required>
      <input type="password" name="password" placeholder="Contraseña" required>
      <button type="submit">Registrarse</button>
    </form>
  </div>

  <div id="formEmpleado" class="contact-form" style="display: none;">
    <h3>Registro de Empleado</h3>
    <form action="procesar_registro.php" method="post">
      <input type="hidden" name="tipo" value="empleado">
      <input type="text" name="nombre" placeholder="Nombre completo" required>
      <input type="email" name="email" placeholder="Correo electrónico" required>
      <input type="text" name="cargo" placeholder="Cargo" required>
      <input type="password" name="password" placeholder="Contraseña" required>
      <button type="button">Registrarse</button>
    </form>
  </div>
</main>

<script>
  function mostrarFormulario(tipo) {
    document.getElementById('formCliente').style.display = (tipo === 'cliente') ? 'block' : 'none';
    document.getElementById('formEmpleado').style.display = (tipo === 'empleado') ? 'block' : 'none';
  }
</script>
<script src="js/registro.js" defer></script>

<?php include 'includes/footer.php'; ?>