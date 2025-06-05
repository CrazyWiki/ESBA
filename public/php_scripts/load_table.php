<?php
require_once '../../server/database.php'; // путь к твоему подключению БД

if (!isset($_GET['table'])) {
  echo "No table specified.";
  exit();
}

$table = $_GET['table'];

// Проверка, что таблица разрешена
$allowedTables = [
  'Usuarios', 'Clientes', 'Servicios', 'Tarifas',
  'EstadoEnvio', 'Administradores', 'Conductores',
  'Vehiculos', 'Envios', 'DetalleEnvio', 'Feedback'
];

if (!in_array($table, $allowedTables)) {
  echo "Table not allowed.";
  exit();
}

// Загружаем данные
$query = "SELECT * FROM $table";
$result = $conn->query($query);

if (!$result) {
  echo "Error: " . $conn->error;
  exit();
}

// Отображаем данные в таблице
echo "<h2>Tabla: $table</h2>";
echo "<table border='1'><tr>";
while ($field = $result->fetch_field()) {
  echo "<th>{$field->name}</th>";
}
echo "<th>Acciones</th></tr>";

while ($row = $result->fetch_assoc()) {
  echo "<tr>";
  foreach ($row as $cell) {
    echo "<td contenteditable='true'>$cell</td>";
  }
  echo "<td><button class='save-btn'>Guardar</button></td>";
  echo "</tr>";
}

echo "</table>";

// TODO: можно сделать JS для отправки обновлений (fetch POST)
//$conn->close();
echo "<h2>Tabla: $table</h2>";


while ($row = $result->fetch_assoc()) {
  echo "<tr>";
  foreach ($row as $cell) {
    echo "<td contenteditable='true'>$cell</td>";
  }
  echo "<td>
          <button class='save-btn'>Guardar</button>
          <button class='delete-btn'>Eliminar</button>
        </td>";
  echo "</tr>";
}

echo "</table>";

$conn->close();

?>
