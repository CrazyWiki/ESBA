document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('#formEmpleado form');
    const responseDiv = document.createElement('div');
    form.appendChild(responseDiv);

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      const formData = new FormData(form);

      fetch('php_scripts/registrar_empleado.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        responseDiv.innerHTML = data;
        form.reset();
      })
      .catch(error => {
        responseDiv.innerHTML = 'Error al registrar el empleado.';
        console.error('Error:', error);
      });
    });
});
