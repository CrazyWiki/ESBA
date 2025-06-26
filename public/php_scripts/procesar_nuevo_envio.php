<?php
session_start();

// Проверка безопасности: убеждаемся, что пользователь вошел и является клиентом
if (!isset($_SESSION['id_usuario']) || isset($_SESSION['user_role'])) {
    $_SESSION['order_status_msg'] = 'Error: Acceso no autorizado.';
    header("Location: ../login.php");
    exit();
}

// Проверяем, что запрос был методом POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once __DIR__ . '/../../server/database.php';

    // Получаем данные из формы
    $lugar_origen = $_POST['lugar_origen'] ?? '';
    $lugar_destino = $_POST['lugar_destino'] ?? '';
    $km = $_POST['km'] ?? 0;
    // $descripcion = $_POST['descripcion'] ?? 'Paquete'; // Пока не используем, но можно будет для DetalleEnvio

    $id_usuario_actual = $_SESSION['id_usuario'];
    $id_cliente = null;

    // --- Логика для создания заказа ---

    // 1. Находим ID клиента по ID пользователя
    $stmt_cliente = $conn->prepare("SELECT id_cliente FROM Clientes WHERE Usuarios_id_usuario = ?");
    $stmt_cliente->bind_param("i", $id_usuario_actual);
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();
    if ($cliente_row = $result_cliente->fetch_assoc()) {
        $id_cliente = $cliente_row['id_cliente'];
    }
    $stmt_cliente->close();

    if ($id_cliente === null) {
        $_SESSION['order_status_msg'] = "Error: No se encontró un perfil de cliente para crear el pedido.";
        header("Location: ../area_personal_cliente.php");
        exit();
    }
    
    // 2. ВАЖНОЕ УПРОЩЕНИЕ: Назначаем случайный автомобиль для доставки
    // В реальной системе здесь была бы сложная логика диспетчеризации.
    $vehiculo_result = $conn->query("SELECT vehiculos_id FROM Vehiculos ORDER BY RAND() LIMIT 1");
    $id_vehiculo = $vehiculo_result->fetch_assoc()['vehiculos_id'];

    // 3. Получаем ID для статуса "Pendiente"
    $estado_result = $conn->query("SELECT estado_envio_id FROM EstadoEnvio WHERE descripcion = 'Pendiente' LIMIT 1");
    $id_estado_pendiente = $estado_result->fetch_assoc()['estado_envio_id'];

    // 4. Валидация и вставка данных
    if (!empty($lugar_origen) && !empty($lugar_destino) && $km > 0 && $id_vehiculo && $id_estado_pendiente) {
        
        $sql = "INSERT INTO Envios (fecha_envio, lugar_origen, lugar_distinto, km, EstadoEnvio_estado_envio_id1, Vehiculos_vehiculos_id, Clientes_id_cliente) 
                VALUES (NOW(), ?, ?, ?, ?, ?, ?)";
        
        $stmt_insert = $conn->prepare($sql);
        // Используем оригинальные имена столбцов из вашей схемы
        $stmt_insert->bind_param("ssdiis", $lugar_origen, $lugar_destino, $km, $id_estado_pendiente, $id_vehiculo, $id_cliente);
        
        if ($stmt_insert->execute()) {
            $_SESSION['order_status_msg'] = "¡Tu pedido ha sido creado exitosamente!";
        } else {
            $_SESSION['order_status_msg'] = "Error al crear el pedido: " . $stmt_insert->error;
        }
        $stmt_insert->close();

    } else {
        $_SESSION['order_status_msg'] = "Error: Por favor, complete todos los campos del formulario.";
    }

    $conn->close();
} else {
    $_SESSION['order_status_msg'] = "Error: Método de solicitud no válido.";
}

// Перенаправляем клиента обратно в его личный кабинет
header("Location: ../area_personal_cliente.php");
exit();
?>