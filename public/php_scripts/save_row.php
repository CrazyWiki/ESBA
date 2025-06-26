<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../../server/database.php';

// Убедимся, что заголовки отправляются до любого вывода
header('Content-Type: application/json');

// Проверка подключения к БД
if ($conn->connect_error) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к БД: ' . $conn->connect_errno]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$tableName = $input['table'] ?? null;
$pkFieldName = $input['pkField'] ?? null;
$rowData = $input['data'] ?? null;

// Валидация входных данных
if (empty($tableName) || empty($pkFieldName) || empty($rowData) || !is_array($rowData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных: отсутствует имя таблицы, PK или данные строки.']);
    $conn->close();
    exit();
}
$pkValue = $rowData[$pkFieldName] ?? null;
if ($pkValue === null || $pkValue === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Значение PK ('$pkFieldName') не предоставлено или пусто для операции обновления."]);
    $conn->close();
    exit();
}

// ++++++++++ УПРОЩЕННЫЙ И НАДЕЖНЫЙ БЛОК ХЕШИРОВАНИЯ ПАРОЛЯ ++++++++++
$tableNameLower = strtolower($tableName);

// Определяем, с какой таблицей и колонкой пароля мы работаем
$passwordColumnInDb = null;
if ($tableNameLower === 'administradores') {
    $passwordColumnInDb = 'password';
} elseif ($tableNameLower === 'usuarios') {
    $passwordColumnInDb = 'password_hash';
}

// Проверяем, было ли отправлено поле пароля и не пустое ли оно
if ($passwordColumnInDb && isset($rowData[$passwordColumnInDb]) && !empty(trim($rowData[$passwordColumnInDb]))) {
    
    // ПРИМЕЧАНИЕ: Мы предполагаем, что если в поле пароля что-то есть, это НОВЫЙ пароль.
    // Если фронтенд отправляет старый хеш, эта логика захеширует хеш, что неправильно.
    // Фронтенд должен отправлять поле пароля только тогда, когда он действительно изменен.
    
    $plainPassword = trim($rowData[$passwordColumnInDb]);
    
    // Хешируем пароль
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    if ($hashedPassword === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Критическая ошибка хеширования пароля на сервере.']);
        $conn->close();
        exit();
    }
    
    // Заменяем пароль в данных на его хеш
    $rowData[$passwordColumnInDb] = $hashedPassword;

} elseif ($passwordColumnInDb) {
    // Если поле пароля пришло, но оно пустое, удаляем его из массива данных,
    // чтобы не перезаписать существующий пароль в БД пустым значением.
    unset($rowData[$passwordColumnInDb]);
}
// ++++++++++ КОНЕЦ БЛОКА ХЕШИРОВАНИЯ ПАРОЛЯ ++++++++++


// --- Дальнейшая логика построения и выполнения UPDATE запроса ---

// Получаем актуальные колонки таблицы
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
    // Обновляем только существующие в таблице колонки и не трогаем первичный ключ
    if (in_array($columnName, $actualTableColumns) && $columnName !== $pkFieldName) {
        $setParts[] = "`" . $conn->real_escape_string($columnName) . "` = ?";
        $params[] = ($value === '' || $value === null) ? null : $value;
        
        // Определяем типы параметров для bind_param
        if (is_int($value)) $paramTypes .= "i";
        else if (is_float($value)) $paramTypes .= "d";
        else $paramTypes .= "s";
    }
}

if (empty($setParts)) {
    echo json_encode(['success' => true, 'message' => 'Нет данных для обновления.']);
    $conn->close();
    exit();
}

// Добавляем значение PK для условия WHERE
$params[] = $pkValue;
if (is_int($pkValue)) $paramTypes .= "i"; else $paramTypes .= "s";

$sql = "UPDATE `" . $conn->real_escape_string($tableName) . "` SET " . implode(", ", $setParts) . " WHERE `" . $conn->real_escape_string($pkFieldName) . "` = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка подготовки SQL UPDATE: ' . $conn->error]);
    $conn->close();
    exit();
}

if (!empty($paramTypes) && !empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Строка успешно обновлена.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Запрос выполнен, но данные не изменились.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка выполнения SQL UPDATE: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>