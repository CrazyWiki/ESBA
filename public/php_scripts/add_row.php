<?php
// ЭТО ДОЛЖНО БЫТЬ САМОЙ ПЕРВОЙ СТРОКОЙ ВЫВОДА (или до любого вывода)
header('Content-Type: application/json');

// Сначала подключаем auth_check, так как он может завершиться с JSON-ошибкой авторизации
require_once __DIR__ . '/auth_check.php'; // auth_check.php также должен вызывать session_start()

// Затем подключаем обновленный server/database.php
// Путь от php_scripts/ до server/database.php
require_once __DIR__ . '/../../server/database.php';

// !!!!! КРИТИЧЕСКИ ВАЖНАЯ ПРОВЕРКА !!!!!
if ($conn->connect_error) {
    http_response_code(503); // Service Unavailable или 500 Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка подключения к базе данных: (' . $conn->connect_errno . ') ' . $conn->connect_error
    ]);
    exit(); // Обязательно завершаем выполнение
}

// Остальная логика add_row.php
$input = json_decode(file_get_contents('php://input'), true);
$tableName = $input['table'] ?? null;

if (empty($tableName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Имя таблицы не указано.']);
    $conn->close(); // Закрываем соединение перед выходом
    exit();
}

// ---- Валидация имени таблицы ----
$allowedTables = [];
$resultValidation = $conn->query("SHOW TABLES");
if ($resultValidation) {
    while ($rowValidation = $resultValidation->fetch_array()) {
        $allowedTables[] = $rowValidation[0];
    }
    $resultValidation->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера при проверке списка таблиц: ' . $conn->error]);
    $conn->close();
    exit();
}

if (!in_array($tableName, $allowedTables)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => "Доступ к таблице '$tableName' запрещен или она не существует."]);
    $conn->close();
    exit();
}
// ---- Конец валидации ----

$sql = "INSERT INTO `" . $conn->real_escape_string($tableName) . "` () VALUES ()";

if ($conn->query($sql) === TRUE) {
    $last_id = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'message' => "Новая строка подготовлена (ID: $last_id). Заполните ее и сохраните.",
        'new_row_id' => $last_id
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Ошибка SQL при добавлении строки в таблицу '$tableName': " . $conn->error .
                     ". Убедитесь, что все обязательные поля (NOT NULL) имеют значения по умолчанию или допускают NULL в структуре таблицы."
    ]);
}

$conn->close();
exit();
?>