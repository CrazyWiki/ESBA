<?php
require_once '../../server/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$table = $input['table'];
$id = $input['id'];

$allowedTables = [
  'Usuarios', 'Clientes', 'Servicios', 'Tarifas',
  'EstadoEnvio', 'Administradores', 'Conductores',
  'Vehiculos', 'Envios', 'DetalleEnvio', 'Feedback'
];
if (!in_array($table, $allowedTables)) {
  echo "Table not allowed.";
  exit();
}

// Находим имя первого столбца (id)
$result = $conn->query("DESCRIBE $table");
$row = $result->fetch_assoc();
$primaryKey = $row['Field'];

$sql = "DELETE FROM $table WHERE $primaryKey='$id'";
if ($conn->query($sql)) {
  echo "Fila eliminada correctamente.";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>
