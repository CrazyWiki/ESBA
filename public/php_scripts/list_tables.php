<?php
// Verificar si el usuario está autenticado
require_once __DIR__ . '/auth_check.php';

// Archivo de conexión a la base de datos (debe definir la variable $conn)
require_once __DIR__ . '/../../server/database.php'; 

// Verificar la conexión a la base de datos inmediatamente después de incluir el archivo
if ($conn->connect_error) {
    http_response_code(503); // Service Unavailable
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos: (' . $conn->connect_errno . ') ' . $conn->connect_error
    ]);
    exit();
}

header('Content-Type: application/json');

$tables = [];

// Consulta para obtener todas las tablas de la base de datos (específica de MySQL)
$result = $conn->query("SHOW TABLES");

if ($result) {
    while ($row = $result->fetch_array()) {
        // El nombre de la tabla se encuentra en el primer elemento del array ($row[0])
        $tables[] = $row[0];
    }
    echo json_encode(['success' => true, 'tables' => $tables]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener la lista de tablas: ' . $conn->error
    ]);
}

$conn->close();
?>