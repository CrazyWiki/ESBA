<?php
require_once __DIR__ . '/auth_check.php';
require_once '../server/database.php';

if ($conn->connect_error) {
    http_response_code(503); // Service Unavailable
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos: (' . $conn->connect_errno . ') ' . $conn->connect_error
    ]);
    exit();
}

header('Content-Type: application/json');

if (!isset($_GET['table'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'El nombre de la tabla no se especificó en la solicitud.']);
    exit();
}

$tableName = $_GET['table'];

$allowedTables = [];
$resultValidation = $conn->query("SHOW TABLES");
if ($resultValidation) {
    while ($rowValidation = $resultValidation->fetch_array()) {
        $allowedTables[] = $rowValidation[0];
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo verificar la lista de tablas disponibles: ' . $conn->error]);
    exit();
}

if (!in_array($tableName, $allowedTables)) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => "El acceso a la tabla '$tableName' está prohibido o no existe."]);
    exit();
}
// ---- Fin de la validación ----

// Obtener la estructura de la tabla (columnas e información de la clave primaria)
$columnsMeta = [];
$primaryKeyField = null;
$describeResult = $conn->query("DESCRIBE `" . $conn->real_escape_string($tableName) . "`");

if ($describeResult) {
    while ($col = $describeResult->fetch_assoc()) {
        $columnsMeta[] = $col;
        if (isset($col['Key']) && $col['Key'] == 'PRI') {
            $primaryKeyField = $col['Field'];
        }
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener la estructura de la tabla: ' . $conn->error]);
    exit();
}

// Intentar encontrar auto_increment si no se encontró la PK a través de 'PRI'
if ($primaryKeyField === null) {
    foreach ($columnsMeta as $col) {
        if (isset($col['Extra']) && strpos(strtolower($col['Extra']), 'auto_increment') !== false) {
            $primaryKeyField = $col['Field'];
            break;
        }
    }
}

// Si aún no se encuentra la clave primaria, detener ejecución
if ($primaryKeyField === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "No se pudo determinar la clave primaria para la tabla '$tableName'. Asegúrese de que la tabla tenga una clave primaria (PRIMARY KEY)."]);
    exit();
}

// Obtener todos los datos de la tabla
$rowsData = [];
$queryData = "SELECT * FROM `" . $conn->real_escape_string($tableName) . "`";
$dataResult = $conn->query($queryData);

if ($dataResult) {
    while ($row = $dataResult->fetch_assoc()) {
        $rowsData[] = $row;
    }
    echo json_encode([
        'success' => true,
        'columns' => $columnsMeta,
        'rows' => $rowsData,
        'primaryKey' => $primaryKeyField
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cargar los datos de la tabla: ' . $conn->error]);
}

$conn->close();
?>