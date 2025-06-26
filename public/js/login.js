// Файл: js/login.js
function mostrarFormulario(tipo) {
    // Сначала скрываем все управляемые формы
    document.getElementById('formClientelogin').style.display = 'none';
    document.getElementById('formEmpleadologin').style.display = 'none';
    
    // Показываем нужную
    if (tipo === 'cliente') {
        document.getElementById('formClientelogin').style.display = 'block';
    } else if (tipo === 'empleado') {
        document.getElementById('formEmpleadologin').style.display = 'block';
    }
}

// Опционально: установите одну из форм видимой по умолчанию при загрузке, если нужно
// window.addEventListener('DOMContentLoaded', () => {
//   mostrarFormulario('cliente'); // Показать форму клиента по умолчанию
//   // или mostrarFormulario(''); // Скрыть все, если кнопки должны быть нажаты первыми
// });
