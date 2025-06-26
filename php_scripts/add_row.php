<?php
// Archivo: public/php_scripts/add_row.php
// Propósito: Procesa solicitudes AJAX para añadir una nueva fila a una tabla de la base de datos.
// Se espera que 'auth_check.php' ya haya verificado la sesión y los permisos.

session_start(); 

header('Content-Type: application/json; charset=utf-8');

require_once  '../server/database.php';

if ($conn->connect_error) {
    http_response_code(503); 
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit(); 
}

$input = json_decode(file_get_contents('php://input'), true);
$tableName = $input['table'] ?? null;
$rowData = $input['data'] ?? []; 

if (empty($tableName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: El nombre de la tabla no fue especificado.']);
    $conn->close(); 
    exit();
}

$allowedTables = [];
$resultValidation = $conn->query("SHOW TABLES");
if ($resultValidation) {
    while ($rowValidation = $resultValidation->fetch_array()) {
        $allowedTables[] = $rowValidation[0]; 
    }
    $resultValidation->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al verificar la lista de tablas disponibles: ' . $conn->error]);
    $conn->close();
    exit();
}

if (!in_array($tableName, $allowedTables)) {
    http_response_code(403); 
    echo json_encode(['success' => false, 'message' => "Acceso denegado a la tabla '$tableName' o la tabla no existe."]);
    $conn->close();
    exit();
}

$actualTableColumnsMeta = [];
$primaryKeyField = null; 
$describeResult = $conn->query("DESCRIBE `" . $conn->real_escape_string($tableName) . "`");
if ($describeResult) {
    while ($col = $describeResult->fetch_assoc()) {
        $actualTableColumnsMeta[$col['Field']] = $col;
        if ($col['Key'] === 'PRI') {
            $primaryKeyField = $col['Field'];
        }
    }
    $describeResult->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener la estructura de la tabla: ' . $conn->error]);
    $conn->close();
    exit();
}

$columnsForInsert = [];
$valuesForInsert = [];  
$paramTypes = "";    

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
        echo json_encode(['success' => false, 'message' => 'Error al hashear la contraseña.']);
        $conn->close();
        exit();
    }
    $rowData[$passwordColumnInDb] = $hashedPassword; 
} elseif ($passwordColumnInDb && array_key_exists($passwordColumnInDb, $rowData)) {
    unset($rowData[$passwordColumnInDb]);
}

foreach ($actualTableColumnsMeta as $columnName => $colMeta) {
    if ($columnName === $primaryKeyField && strpos($colMeta['Extra'], 'auto_increment') !== false) {
        continue;
    }

    if (array_key_exists($columnName, $rowData)) {
        $columnsForInsert[] = "`" . $conn->real_escape_string($columnName) . "`";
        $valuesForInsert[] = $rowData[$columnName];
        if (is_int($rowData[$columnName])) $paramTypes .= "i";
        else if (is_float($rowData[$columnName])) $paramTypes .= "d";
        else $paramTypes .= "s";
    } 
}


if (empty($columnsForInsert)) {
    $sql = "INSERT INTO `" . $conn->real_escape_string($tableName) . "` () VALUES ()";
    $stmt = $conn->prepare($sql);
    $bindNeeded = false;
} else {
    $sql = "INSERT INTO `" . $conn->real_escape_string($tableName) . "` (" . implode(", ", $columnsForInsert) . ") VALUES (" . implode(", ", array_fill(0, count($columnsForInsert), '?')) . ")";
    $stmt = $conn->prepare($sql);
    $bindNeeded = true;
}


if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta SQL INSERT: ' . $conn->error]);
    $conn->close();
    exit();
}

if ($bindNeeded) {
    $stmt->bind_param($paramTypes, ...$valuesForInsert);
}

if ($stmt->execute()) {
    $last_id = $conn->insert_id; 
    
    if ($primaryKeyField && strpos($actualTableColumnsMeta[$primaryKeyField]['Extra'], 'auto_increment') !== false && $last_id > 0) {
        echo json_encode(['success' => true, 'message' => "Fila añadida exitosamente (ID: $last_id).", 'new_row_id' => $last_id]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Fila añadida exitosamente. ID no disponible o no generado.', 'new_row_id' => $last_id]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Error al añadir la fila a la tabla '$tableName': " . $conn->error .
                     ". Asegúrese de que todas las columnas obligatorias (NOT NULL sin DEFAULT) tengan un valor especificado."
    ]);
}

$stmt->close();
$conn->close();
exit();