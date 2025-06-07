<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../../server/database.php'; // Убедитесь, что этот файл не выводит HTML при ошибке подключения

// Проверка подключения к БД (если server/database.php не делает exit при ошибке)
if ($conn->connect_error) {
    header('Content-Type: application/json'); // Устанавливаем заголовок ПЕРЕД выводом
    http_response_code(503); // или 500
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка подключения к БД: (' . $conn->connect_errno . ') ' . $conn->connect_error
    ]);
    exit();
}

header('Content-Type: application/json'); // Эта строка должна быть до любого другого вывода

$input = json_decode(file_get_contents('php://input'), true);
$tableName = $input['table'] ?? null;
$pkFieldName = $input['pkField'] ?? null;
$rowData = $input['data'] ?? null;
$isNewPasswordProvided = $input['isNewPasswordProvided'] ?? false; // Получаем флаг из JS

// --- Логирование для отладки (можно будет убрать) ---
$log_file = __DIR__ . '/save_row_debug.log';
$timestamp = date("Y-m-d H:i:s");
file_put_contents($log_file, "---- {$timestamp} SAVE REQUEST (Password Hashing Check) ----\n", FILE_APPEND);
file_put_contents($log_file, "RAW INPUT: " . file_get_contents('php://input') . "\n", FILE_APPEND);
file_put_contents($log_file, "DECODED INPUT: " . print_r($input, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "Table: {$tableName}, PK Field: {$pkFieldName}, isNewPasswordProvided: " . ($isNewPasswordProvided ? 'true' : 'false') . "\n", FILE_APPEND);
file_put_contents($log_file, "RowData for save: " . print_r($rowData, true) . "\n", FILE_APPEND);
// --- Конец логирования ---


if (empty($tableName) || empty($pkFieldName) || empty($rowData) || !is_array($rowData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных: отсутствует имя таблицы, PK или данные строки.']);
    $conn->close(); // Закрываем соединение
    exit();
}

$pkValue = $rowData[$pkFieldName] ?? null;
if ($pkValue === null || $pkValue === '') { //  Для UPDATE pkValue не должен быть пустым
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Значение PK ('$pkFieldName') не предоставлено или пусто для операции обновления."]);
    $conn->close();
    exit();
}

// ---- Валидация имени таблицы ----
$allowedTables = [];
$stmtVal = $conn->query("SHOW TABLES");
if ($stmtVal) {
    while ($rowVal = $stmtVal->fetch_array()) { $allowedTables[] = $rowVal[0]; }
    $stmtVal->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка проверки таблиц: ' . $conn->error]);
    $conn->close();
    exit();
}

if (!in_array(strtolower($tableName), array_map('strtolower', $allowedTables))) { // Сравнение без учета регистра
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => "Доступ к таблице '$tableName' запрещен или она не существует."]);
    $conn->close();
    exit();
}
// ---- Конец валидации ----

$actualTableColumns = [];
$describeResult = $conn->query("DESCRIBE `" . $conn->real_escape_string($tableName) . "`");
if ($describeResult) {
    while ($col = $describeResult->fetch_assoc()) { $actualTableColumns[] = $col['Field']; }
    $describeResult->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Не удалось получить структуру таблицы: ' . $conn->error]);
    $conn->close();
    exit();
}


// ++++++++++ БЛОК ХЕШИРОВАНИЯ ПАРОЛЯ ++++++++++
// Сравниваем имя таблицы без учета регистра
if (strtolower($tableName) === 'administradores' && isset($rowData['password'])) { // Убедитесь, что имя колонки 'password' верное
    file_put_contents($log_file, "[DEBUG] Handling 'administradores' password. Current password data: '" . ($rowData['password'] ?? 'NOT SET') . "'. Flag isNewPasswordProvided: " . ($isNewPasswordProvided ? 'true' : 'false') . "\n", FILE_APPEND);
    
    if ($isNewPasswordProvided && !empty(trim($rowData['password']))) {
        $plainPassword = trim($rowData['password']);
        file_put_contents($log_file, "[DEBUG] Attempting to hash password: '" . $plainPassword . "'\n", FILE_APPEND);
        
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        if ($hashedPassword === false) {
            file_put_contents($log_file, "[ERROR] password_hash() returned false!\n", FILE_APPEND);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Критическая ошибка хеширования пароля на сервере.']);
            $conn->close();
            exit();
        }
        $rowData['password'] = $hashedPassword; // ЗАМЕНЯЕМ ПАРОЛЬ НА ХЕШ
        file_put_contents($log_file, "[DEBUG] Password hashed. New value in rowData['password']: '" . $rowData['password'] . "'\n", FILE_APPEND);
    } else {
        // Если новый пароль не предоставлен или пустой, удаляем поле password из данных, чтобы оно не обновлялось в БД.
        unset($rowData['password']);
        file_put_contents($log_file, "[DEBUG] Password field 'password' unset. Password will not be updated.\n", FILE_APPEND);
    }
} else if (strtolower($tableName) === 'administradores' && $isNewPasswordProvided && !isset($rowData['password'])) {
    // Этот случай не должен происходить, если JS работает правильно (флаг true, а поля password нет)
    file_put_contents($log_file, "[WARNING] isNewPasswordProvided is true, but 'password' key is not in rowData for 'administradores' table. Check JS data collection or if password field name is correct.\n", FILE_APPEND);
}
// ++++++++++ КОНЕЦ БЛОКА ХЕШИРОВАНИЯ ПАРОЛЯ ++++++++++

file_put_contents($log_file, "[DEBUG] Data for SQL SET parts: " . print_r($rowData, true) . "\n", FILE_APPEND);


$setParts = [];
$params = [];
$paramTypes = "";

foreach ($rowData as $columnName => $value) {
    // Обновляем только существующие в таблице колонки и не обновляем первичный ключ напрямую в SET
    if (in_array($columnName, $actualTableColumns) && $columnName !== $pkFieldName) {
        $setParts[] = "`" . $conn->real_escape_string($columnName) . "` = ?";
        $currentVal = ($value === '' || $value === null) ? null : $value;
        $params[] = $currentVal;
        
        if ($currentVal === null) $paramTypes .= "s";
        else if (is_int($currentVal)) $paramTypes .= "i";
        else if (is_float($currentVal) || is_double($currentVal)) $paramTypes .= "d";
        else $paramTypes .= "s";
    }
}

if (empty($setParts)) {
    echo json_encode(['success' => true, 'message' => 'Нет данных для обновления (возможно, изменения не были внесены, или редактировался только PK, или не было полей для обновления).']);
    $conn->close();
    exit();
}

// Добавляем значение PK для условия WHERE
$params[] = $pkValue;
// Определяем тип для PK
if (is_int($pkValue)) $paramTypes .= "i";
else if (is_float($pkValue) || is_double($pkValue)) $paramTypes .= "d";
else $paramTypes .= "s";

$sql = "UPDATE `" . $conn->real_escape_string($tableName) . "` SET " . implode(", ", $setParts) . " WHERE `" . $conn->real_escape_string($pkFieldName) . "` = ?";
file_put_contents($log_file, "[DEBUG] SQL: " . $sql . "\nPARAMS: " . print_r($params, true) . "\nTYPES: " . $paramTypes . "\n", FILE_APPEND); // Логируем SQL

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    file_put_contents($log_file, "[ERROR] SQL Prepare Error: " . $conn->error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Ошибка подготовки SQL UPDATE: ' . $conn->error]);
    $conn->close();
    exit();
}

// Передача параметров в bind_param должна быть по ссылке, если PHP < 8.1 для spread operator
// Но для PHP 5.6+ ...$params должно работать с call_user_func_array или если $params содержит ссылки.
// Безопаснее всего - убедиться, что $params содержит нужные значения.
if (!empty($paramTypes) && !empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}


if ($stmt->execute()) {
    file_put_contents($log_file, "[INFO] SQL Execute Success. Affected rows: " . $stmt->affected_rows . "\n", FILE_APPEND);
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Строка успешно обновлена.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Запрос выполнен, но данные не изменились (возможно, не было изменений или строка не найдена).']);
    }
} else {
    http_response_code(500);
    file_put_contents($log_file, "[ERROR] SQL Execute Error: " . $stmt->error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Ошибка выполнения SQL UPDATE: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
file_put_contents($log_file, "---- END SAVE REQUEST ----\n\n", FILE_APPEND);
?>