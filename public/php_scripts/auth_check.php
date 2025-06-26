<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Предполагаем, что при успешном входе вы устанавливаете $_SESSION['admin_id']
if (!isset($_SESSION['admin_id'])) {
    // Для AJAX запросов (которые используются нашими php_scripts)
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Доступ запрещен. Пожалуйста, авторизуйтесь.']);
        exit();
    } else {
        // Для прямых обращений к скрипту (если кто-то попытается открыть их в браузере)
        // лучше перенаправить на страницу входа или просто отказать в доступе.
        // Путь к login.php от папки php_scripts
        header("Location: ../login.php");
        exit();
    }
}
// Если мы здесь, пользователь авторизован.
// Можно добавить дополнительные проверки, например, роль пользователя, если необходимо.
?>