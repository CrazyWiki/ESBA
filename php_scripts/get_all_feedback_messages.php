<?php
header('Content-Type: application/json');
session_start();
$allowed = ['administrador','gerente de ventas'];
if (empty($_SESSION['user_id']) || 
    !in_array(strtolower($_SESSION['user_role'] ?? ''), $allowed, true)) 
{
    http_response_code(403);
    echo json_encode(['feedback'=>[], 'error'=>'Acceso denegado.']);
    exit();
}
// --- Fin verificación de autorización ---

require_once '../server/database.php';

$response = ['feedback' => [], 'error' => ''];

$where = [];
$params = [];
$types = '';

if (isset($_GET['fecha']) && $_GET['fecha'] !== '') {
    $where[] = 'fecha_envio = ?';
    $params[] = $_GET['fecha'];
    $types .= 's';
}
if (isset($_GET['nombre_cliente']) && $_GET['nombre_cliente'] !== '') {
    $where[] = 'name LIKE ?';
    $params[] = '%' . $_GET['nombre_cliente'] . '%';
    $types .= 's';
}
if (isset($_GET['email_cliente']) && $_GET['email_cliente'] !== '') {
    $where[] = 'email LIKE ?';
    $params[] = '%' . $_GET['email_cliente'] . '%';
    $types .= 's';
}

$sql = "SELECT
            idFeedback AS idFeedback,
            name,
            email,
            message,
            fecha_envio,
            employee_comment
        FROM `Feedback`";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY fecha_envio DESC';

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['feedback'=>[], 'error'=>'Error en prepare(): '.$conn->error]);
    exit();
}
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$response = ['feedback'=>[], 'error'=>''];
while ($row = $result->fetch_assoc()) {
    $response['feedback'][] = $row;
}
$stmt->close();
$conn->close();
echo json_encode($response);
exit;