<?php
require_once '../../server/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$table = $input['table'];

$allowedTables = [
  'Usuarios', 'Clientes', 'Servicios', 'Tarifas',
  'EstadoEnvio', 'Administradores', 'Conductores',
  'Vehiculos', 'Envios', 'DetalleEnvio', 'Feedback'
];
if (!in_array($table, $allowedTables)) {
  echo "Table not allowed.";
  exit();
}

// Получаем структуру таблицы
$result = $conn->query("DESCRIBE $table");
$fields = [];
$i = 0;
while ($row = $result->fetch_assoc()) {
  if ($i == 0) {
    $fields[] = "NULL";  // автоинкремент
  } else {
    $fields[] = "''";    // пустая строка
  }
  $i++;
}

$values = implode(',', $fields);
$sql = "INSERT INTO $table VALUES ($values)";
if ($conn->query($sql)) {
  $newId = $conn->insert_id;  // получаем ID новой строки
  echo "Fila agregada correctamente. ID: $newId";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>
