document.addEventListener('DOMContentLoaded', function () {
  // CLIENTE
  const formCliente = document.querySelector('#formCliente form');
  const responseDivCliente = document.createElement('div');
  formCliente.appendChild(responseDivCliente);

  formCliente.addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(formCliente);

    fetch('php_scripts/registrar_cliente.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.text())
      .then(data => {
        responseDivCliente.innerHTML = data;
        if (!data.includes("Error") && !data.includes("ya está registrado")) {
          formCliente.reset();
        }
      })
      .catch(error => {
        responseDivCliente.innerHTML = 'Error al registrar el cliente.';
        console.error('Error:', error);
      });
  });

  // EMPLEADO
  const formEmpleado = document.querySelector('#formEmpleado form');
  const responseDivEmpleado = document.createElement('div');
  formEmpleado.appendChild(responseDivEmpleado);

  formEmpleado.addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(formEmpleado);

    fetch('php_scripts/registrar_empleado.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.text())
      .then(data => {
        responseDivEmpleado.innerHTML = data;
        if (!data.includes("Error") && !data.includes("ya está registrado")) {
          formEmpleado.reset();
        }
      })
      .catch(error => {
        responseDivEmpleado.innerHTML = 'Error al registrar el empleado.';
        console.error('Error:', error);
      });
  });
});
