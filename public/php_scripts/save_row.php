<?php
require_once '../../server/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$table = $input['table'];
$data = $input['data'];

// Для безопасности — проверь таблицу
$allowedTables = [
  'Usuarios', 'Clientes', 'Servicios', 'Tarifas',
  'EstadoEnvio', 'Administradores', 'Conductores',
  'Vehiculos', 'Envios', 'DetalleEnvio', 'Feedback'
]; // аналогично load_table.php
if (!in_array($table, $allowedTables)) {
  echo "Table not allowed.";
  exit();
}

$id = $data[0];
$columns = []; // Названия полей для UPDATE
$result = $conn->query("DESCRIBE $table");
$i = 0;
while ($row = $result->fetch_assoc()) {
  if ($i > 0) { // пропускаем id
    $columns[] = $row['Field'];
  }
  $i++;
}

$updates = [];
for ($i = 1; $i < count($data); $i++) {
  $updates[] = "{$columns[$i-1]}='" . $conn->real_escape_string($data[$i]) . "'";
}

$sql = "UPDATE $table SET " . implode(',', $updates) . " WHERE {$columns[0]}='$id'";
if ($conn->query($sql)) {
  echo "Fila actualizada correctamente.";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>
