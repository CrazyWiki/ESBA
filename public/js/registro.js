document.addEventListener('DOMContentLoaded', function () {
  const formularios = [
    { id: 'formCliente', script: 'php_scripts/registrar_cliente.php' },
    { id: 'formEmpleado', script: 'php_scripts/registrar_empleado.php' }
  ];

  formularios.forEach(({ id, script }) => {
    const formDiv = document.getElementById(id);
    const form = formDiv.querySelector('form');
    const responseDiv = document.createElement('div');
    responseDiv.classList.add('respuesta-registro');
    form.appendChild(responseDiv);

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      const formData = new FormData(form);

      fetch(script, {
        method: 'POST',
        body: formData
      })
        .then(response => response.text())
        .then(data => {
          responseDiv.innerHTML = data;
          if (data.toLowerCase().includes("correctamente")) {
            form.reset(); // solo reseteamos si fue exitoso
          }
        })
        .catch(error => {
          responseDiv.innerHTML = 'Error al procesar el formulario.';
          console.error('Error:', error);
        });
    });
  });
});
