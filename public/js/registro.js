document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('#formCliente form');
  let responseDiv = document.getElementById('registroResponse');

  // Si no existe, crearlo
  if (!responseDiv) {
    responseDiv = document.createElement('div');
    responseDiv.id = 'registroResponse';
    form.appendChild(responseDiv);
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    const formData = new FormData(form);

    fetch('php_scripts/registrar_cliente.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.text())
    .then(data => {
      responseDiv.innerHTML = data;
      // Resetear solo si el registro fue exitoso
      if (data.includes('correctamente')) {
        form.reset();
      }
    })
    .catch(error => {
      responseDiv.innerHTML = 'Error al registrar el cliente.';
      console.error('Error:', error);
    });
  });
});
