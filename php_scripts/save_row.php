<?php
// Archivo: public/php_scripts/update_row.php
// Propósito: Procesa solicitudes AJAX para actualizar una fila en una tabla de la base de datos.
// Se espera que 'auth_check.php' ya haya verificado la sesión y los permisos.
require_once __DIR__ . '/auth_check.php';
require_once '../server/database.php';

header('Content-Type: application/json');

if ($conn->connect_error) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Error al conectarse a la base de datos: ' . $conn->connect_errno]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$tableName = $input['table'] ?? null;
$pkFieldName = $input['pkField'] ?? null;
$rowData = $input['data'] ?? null;

if (empty($tableName) || empty($pkFieldName) || empty($rowData) || !is_array($rowData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No hay suficientes datos: falta el nombre de la tabla, la clave primaria o los datos de la fila.']);
    $conn->close();
    exit();
}
$pkValue = $rowData[$pkFieldName] ?? null;
if ($pkValue === null || $pkValue === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "No se proporciona el valor PK ('$pkFieldName') o está vacío para la operación de actualización."]);
    $conn->close();
    exit();
}

// ==========================================
// === LÓGICA DE HASHEO DE CONTRASEÑAS (REFINADA) ===
// ==========================================
$tableNameLower = strtolower($tableName);
$passwordColumnInDb = null;
if ($tableNameLower === 'administradores') {
    $passwordColumnInDb = 'password';
} elseif ($tableNameLower === 'usuarios') {
    $passwordColumnInDb = 'password_hash';
}

if ($passwordColumnInDb && isset($rowData[$passwordColumnInDb]) && !empty(trim($rowData[$passwordColumnInDb]))) {

    $plainPassword = trim($rowData[$passwordColumnInDb]);
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    if ($hashedPassword === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error crítico de hashing de contraseña en el servidor.']);
        $conn->close();
        exit();
    }
    $rowData[$passwordColumnInDb] = $hashedPassword;

} elseif ($passwordColumnInDb) {
    unset($rowData[$passwordColumnInDb]);
}


$actualTableColumns = [];
$describeResult = $conn->query("DESCRIBE `" . $conn->real_escape_string($tableName) . "`");
if ($describeResult) {
    while ($col = $describeResult->fetch_assoc()) { $actualTableColumns[] = $col['Field']; }
    $describeResult->close();
}

$setParts = [];
$params = [];
$paramTypes = "";

foreach ($rowData as $columnName => $value) {
    if (in_array($columnName, $actualTableColumns) && $columnName !== $pkFieldName) {
        $setParts[] = "`" . $conn->real_escape_string($columnName) . "` = ?";
        $params[] = ($value === '' || $value === null) ? null : $value;
        
        if (is_int($value)) $paramTypes .= "i";
        else if (is_float($value)) $paramTypes .= "d";
        else $paramTypes .= "s";
    }
}

if (empty($setParts)) {
    echo json_encode(['success' => true, 'message' => 'No hay datos para actualizar.']);
    $conn->close();
    exit();
}

$params[] = $pkValue;
if (is_int($pkValue)) $paramTypes .= "i"; else $paramTypes .= "s";

$sql = "UPDATE `" . $conn->real_escape_string($tableName) . "` SET " . implode(", ", $setParts) . " WHERE `" . $conn->real_escape_string($pkFieldName) . "` = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al preparar SQL UPDATE: ' . $conn->error]);
    $conn->close();
    exit();
}

if (!empty($paramTypes) && !empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'La fila se actualizó exitosamente.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'La solicitud se completó, pero los datos no se modificaron.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de ejecución de SQL UPDATE: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>