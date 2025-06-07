<?php
// server/database.php

$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "esbaproj";

// Попытка создать соединение
$conn = new mysqli($servername, $username, $password, $dbname);

// НЕ используем die() здесь.
// Ошибка подключения $conn->connect_error будет проверена в вызывающих скриптах.

// Устанавливаем кодировку, только если соединение успешно (или попытка была сделана)
// Это предотвратит ошибку, если $conn не является объектом из-за полной неудачи new mysqli()
// (хотя $conn->connect_error обычно покрывает это)
if ($conn && !$conn->connect_error) {
    $conn->set_charset("utf8mb4");
}
?>