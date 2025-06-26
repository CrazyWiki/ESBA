<?php
require_once __DIR__ . '/auth_check.php';
require_once '../server/database.php';
if ($conn->connect_error) {
    http_response_code(503); 
    echo json_encode([
        'success' => false,
        'message' => 'Error al conectarse a la base de datos: (' . $conn->connect_errno . ') ' . $conn->connect_error
    ]);
    exit();
}
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$tableName = $input['table'] ?? null;
$pkFieldName = $input['pkField'] ?? null;
$idValue = $input['id'] ?? null;

if (empty($tableName) || empty($pkFieldName) || $idValue === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No hay suficientes datos: falta el nombre de la tabla, PK o ID.']);
    exit();
}


$allowedTables = [];
$stmtVal = $conn->query("SHOW TABLES");
if ($stmtVal) { while ($rowVal = $stmtVal->fetch_array()) { $allowedTables[] = $rowVal[0]; } $stmtVal->close(); }
else { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Error de validación de tabla: ' . $conn->error]); exit(); }

if (!in_array($tableName, $allowedTables)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => "No se permite la eliminación de la tabla  '$tableName' "]);
    exit();
}


$sql = "DELETE FROM `" . $conn->real_escape_string($tableName) . "` WHERE `" . $conn->real_escape_string($pkFieldName) . "` = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al preparar SQL DELETE: ' . $conn->error]);
    exit();
}

$paramType = 's';
if (is_int($idValue)) $paramType = 'i';
else if (is_float($idValue) || is_double($idValue)) $paramType = 'd';

$stmt->bind_param($paramType, $idValue);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'La línea fue eliminada exitosamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la línea o no se eliminó.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de ejecución de SQL DELETE: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>