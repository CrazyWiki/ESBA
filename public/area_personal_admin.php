<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<h1>Panel de Administración</h1>

<div class="admin-buttons">
  <?php
  // Список таблиц для кнопок
  $tables = [
    'Usuarios', 'Clientes', 'Servicios', 'Tarifas',
    'EstadoEnvio', 'Administradores', 'Conductores',
    'Vehiculos', 'Envios', 'DetalleEnvio', 'Feedback'
  ];
  foreach ($tables as $table) {
    echo "<button class='table-button' data-table='$table'>$table</button> ";
  }
  ?>
</div>



<div id="table-container">
  <!-- Здесь будет загружаться таблица для редактирования -->
</div>

<!-- Кнопка добавления новой записи -->
<button id="add-row-btn">Добавить новую строку</button>

<script>
// При клике на кнопку таблицы — загружаем таблицу
document.querySelectorAll('.table-button').forEach(btn => {
  btn.addEventListener('click', () => {
    const table = btn.getAttribute('data-table');

    // Делаем активной эту кнопку
    document.querySelectorAll('.table-button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    fetch(`php_scripts/load_table.php?table=${table}`)
      .then(response => response.text())
      .then(html => {
        document.getElementById('table-container').innerHTML = html;

        // Показываем кнопку добавления строки
        document.getElementById('add-row-btn').style.display = 'inline-block';
        document.getElementById('add-row-btn').setAttribute('data-table', table);
      });
  });
});

// Обработка событий на странице (сохранение / удаление)
document.addEventListener('click', e => {
  // Сохранение
  if (e.target.classList.contains('save-btn')) {
    const row = e.target.closest('tr');
    const cells = row.querySelectorAll('td[contenteditable]');
    const data = [];
    cells.forEach(cell => data.push(cell.textContent));

    const table = document.querySelector('.table-button.active').dataset.table;

    fetch('php_scripts/save_row.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ table, data })
    })
    .then(res => res.text())
    .then(response => alert(response));
  }

  if (e.target.classList.contains('delete-btn')) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta fila?')) return;

    const row = e.target.closest('tr');
    const id = row.querySelector('td').textContent;
    const table = document.querySelector('.table-button.active').dataset.table;

    fetch('php_scripts/delete_row.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ table, id })
    })
    .then(res => res.text())
    .then(response => {
      alert(response);
      row.remove();
    });
  }
});

// Добавление новой записи
document.getElementById('add-row-btn').addEventListener('click', () => {
  const table = document.getElementById('add-row-btn').getAttribute('data-table');

  fetch('php_scripts/add_row.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ table })
  })
  .then(res => res.text())
  .then(response => {
    alert(response);
    // Перезагрузим таблицу
    fetch(`php_scripts/load_table.php?table=${table}`)
      .then(res => res.text())
      .then(html => {
        document.getElementById('table-container').innerHTML = html;
      });
  });
});
</script>

<?php include 'includes/footer.php'; ?>
