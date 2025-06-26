<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../../server/database.php';
if ($conn->connect_error) {
    http_response_code(503); // или 500
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка подключения к БД: (' . $conn->connect_errno . ') ' . $conn->connect_error
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
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных: отсутствует имя таблицы, PK или ID.']);
    exit();
}

// ---- Валидация имени таблицы ----
$allowedTables = [];
$stmtVal = $conn->query("SHOW TABLES");
if ($stmtVal) { while ($rowVal = $stmtVal->fetch_array()) { $allowedTables[] = $rowVal[0]; } $stmtVal->close(); }
else { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Ошибка проверки таблиц: ' . $conn->error]); exit(); }

if (!in_array($tableName, $allowedTables)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => "Удаление из таблицы '$tableName' запрещено."]);
    exit();
}
// ---- Конец валидации ----

$sql = "DELETE FROM `" . $conn->real_escape_string($tableName) . "` WHERE `" . $conn->real_escape_string($pkFieldName) . "` = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка подготовки SQL DELETE: ' . $conn->error]);
    exit();
}

$paramType = 's'; // По умолчанию строка
if (is_int($idValue)) $paramType = 'i';
else if (is_float($idValue) || is_double($idValue)) $paramType = 'd';

$stmt->bind_param($paramType, $idValue);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Строка успешно удалена.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Строка не найдена или не была удалена.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка выполнения SQL DELETE: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>