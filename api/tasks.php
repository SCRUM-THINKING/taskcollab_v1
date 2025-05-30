<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Verificar el método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        // Crear nueva tarea
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['titulo']) || !isset($data['usuario_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            exit;
        }

        try {
            $stmt = $conn->prepare("INSERT INTO tareas (titulo, descripcion, usuario_id) VALUES (?, ?, ?)");
            $stmt->execute([$data['titulo'], $data['descripcion'] ?? null, $data['usuario_id']]);
            
            $taskId = $conn->lastInsertId();
            echo json_encode(['message' => 'Tarea creada exitosamente', 'id' => $taskId]);
        } catch(PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al crear la tarea: ' . $e->getMessage()]);
        }
        break;

    case 'GET':
        // Obtener tareas por usuario
        $usuario_id = $_GET['usuario_id'] ?? null;
        
        if (!$usuario_id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de usuario requerido']);
            exit;
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM tareas WHERE usuario_id = ? ORDER BY fecha_creacion DESC");
            $stmt->execute([$usuario_id]);
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($tareas);
        } catch(PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener las tareas: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Actualizar tarea
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de tarea requerido']);
            exit;
        }

        try {
            $updates = [];
            $params = [];

            if (isset($data['titulo'])) {
                $updates[] = "titulo = ?";
                $params[] = $data['titulo'];
            }

            if (isset($data['descripcion'])) {
                $updates[] = "descripcion = ?";
                $params[] = $data['descripcion'];
            }

            if (isset($data['estado'])) {
                $updates[] = "estado = ?";
                $params[] = $data['estado'];
            }

            if (!empty($updates)) {
                $params[] = $data['id'];
                $sql = "UPDATE tareas SET " . implode(", ", $updates) . " WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['message' => 'Tarea actualizada exitosamente']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'No hay datos para actualizar']);
            }
        } catch(PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la tarea: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Eliminar tarea
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de tarea requerido']);
            exit;
        }

        try {
            $stmt = $conn->prepare("DELETE FROM tareas WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['message' => 'Tarea eliminada exitosamente']);
        } catch(PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar la tarea: ' . $e->getMessage()]);
        }
        break;
}
?> 