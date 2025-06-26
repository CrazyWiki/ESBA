<?php
// Archivo: public/php_scripts/add_feedback_comment.php
// Propósito: Añade o edita un comentario de empleado a un mensaje de feedback específico en la base de datos.
// Utilizado por: area_personal_gerente_feedback.php (a través de AJAX).

session_start();
header('Content-Type: application/json; charset=utf-8');

// --- Verificación de autorización ---
$is_authorized = false;
$allowed_roles = ['administrador', 'gerente de ventas']; 

if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if (in_array(strtolower($_SESSION['user_role']), $allowed_roles, true)) {
        $is_authorized = true;
    }
}

if (!$is_authorized) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado para añadir comentarios.']);
    exit();
}
require_once '../server/database.php'; 

$response = ['success' => false, 'message' => ''];

// Procesa solo solicitudes POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id_feedback = (int)($input['id_feedback'] ?? 0); 
    $employee_comment = trim($input['employee_comment'] ?? ''); 

    // Validación de parámetros de entrada.
    if ($id_feedback <= 0 || $employee_comment === '') { 
        http_response_code(400); 
        $response['message'] = 'Datos insuficientes o inválidos (ID de feedback o comentario vacío).';
        echo json_encode($response);
        $conn->close();
        exit();
    }

    $sql = "UPDATE `Feedback` SET employee_comment = ? WHERE idFeedback = ?"; 
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        http_response_code(500); 
        $response['message'] = 'Error al preparar la consulta de actualización de comentario: ' . $conn->error;
        echo json_encode($response);
        $conn->close();
        exit();
    }

    $stmt->bind_param("si", $employee_comment, $id_feedback);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Comentario guardado correctamente.';
        } else {
            $response['success'] = true; 
            $response['message'] = 'Comentario actualizado, pero no se detectaron cambios.';
        }
    } else {
        http_response_code(500);
        $response['message'] = 'Error al ejecutar la actualización del comentario: ' . $stmt->error;
    }

    $stmt->close(); 

} else { 
    http_response_code(405); 
    $response['message'] = 'Método de solicitud no válido. Este script solo acepta solicitudes POST.';
}

$conn->close(); 
echo json_encode($response); 
exit();