<?php
session_start(); // Начинаем сессию

// Если администратор уже вошел (маловероятно при POST-запросе сюда, но для полноты)
if (isset($_SESSION['admin_id'])) {
    header("Location: ../area_personal_admin.php"); // Путь от php_scripts к корневой папке
    exit();
}

// Подключаемся к БД, используя ваш скрипт
// Путь от текущей папки (php_scripts) к server/database.php
require_once __DIR__ . '/../../server/database.php';

$login_error_message = ''; // Переменная для сообщения об ошибке

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Используем имена полей из новой формы администратора
    $email = $_POST['admin_email'] ?? '';
    $password_input = $_POST['admin_password'] ?? '';

    if (!empty($email) && !empty($password_input)) {
        // ВАЖНО: Пароли в вашей базе данных ДОЛЖНЫ храниться в хешированном виде!
        // Используйте password_hash() при создании/обновлении пароля администратора
        // и password_verify() при проверке входа.
        
        // Запрос к вашей таблице 'Administradores'
        $stmt = $conn->prepare("SELECT idAdministradores, password, role FROM Administradores WHERE email = ?");
        
        if (!$stmt) {
            // Эту ошибку лучше логировать на сервере, а пользователю показывать общее сообщение
            $login_error_message = "Ошибка на сервере (подготовка запроса). Пожалуйста, попробуйте позже.";
            // error_log("Login Admin Prepare Error: " . $conn->error); // Пример логирования
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                // ЗАМЕНИТЕ ЭТУ ПРОВЕРКУ НА ИСПОЛЬЗОВАНИЕ password_verify()!
                // Пример: if (password_verify($password_input, $user['password'])) {
                if ($password_input === $user['password']) { // ЭТО ЗАГЛУШКА - НЕБЕЗОПАСНО В ПРОДАШЕНЕ!
                    // Успешный вход
                    $_SESSION['admin_id'] = $user['idAdministradores'];
                    // Вы можете сохранить и другие данные в сессию при необходимости
                    // $_SESSION['admin_email'] = $email;
                    // $_SESSION['admin_role'] = $user['role'];
                    
                    // Перенаправляем в админ-панель
                    header("Location: ../area_personal_admin.php"); // Путь от php_scripts к корневой папке
                    exit();
                } else {
                    $login_error_message = "Неверный email или пароль администратора.";
                }
            } else {
                // Пользователь с таким email не найден
                $login_error_message = "Неверный email или пароль администратора.";
            }
            $stmt->close();
        }
    } else {
        $login_error_message = "Пожалуйста, введите email и пароль администратора.";
    }
    $conn->close();
} else {
    // Если кто-то обратился к этому скрипту не методом POST
    $login_error_message = "Неверный метод запроса.";
}

// Если вход не удался, сохраняем сообщение об ошибке в сессию и перенаправляем обратно на login.php
$_SESSION['login_error'] = $login_error_message;
header("Location: ../login.php"); // Путь от php_scripts к корневой папке
exit();
?>