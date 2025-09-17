<?php
require 'cors.php';
require 'bd.php'; // Debe definir $pdo como instancia PDO conectada

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Preflight CORS
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->prepare("
                SELECT e.id, e.nombre, e.apellido, e.telefono, e.correo, e.imagen, r.descripcion AS rol, e.rol_id, e.activo
                FROM empleados e
                LEFT JOIN roles r ON e.rol_id = r.id
                WHERE e.eliminado = 0
            ");
            $stmt->execute();
            $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($empleados);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            $nombre = trim($input['nombre'] ?? '');
            $apellido = trim($input['apellido'] ?? '');
            $telefono = trim($input['telefono'] ?? '');
            $correo = trim($input['correo'] ?? '');
            $password = $input['password'] ?? '';
            $rol_id = intval($input['rol_id'] ?? 0);
            $activo = isset($input['activo']) ? intval($input['activo']) : 1;
            $imagen = $input['imagen'] ?? '';

            if (!$nombre || !$correo || !$password || !$rol_id) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Faltan campos requeridos"]);
                exit;
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO empleados (nombre, apellido, telefono, correo, password, rol_id, activo, eliminado, imagen)
                VALUES (:nombre, :apellido, :telefono, :correo, :password, :rol_id, :activo, 0, :imagen)
            ");
            $stmt->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':telefono' => $telefono,
                ':correo' => $correo,
                ':password' => $hashedPassword,
                ':rol_id' => $rol_id,
                ':activo' => $activo,
                ':imagen' => $imagen
            ]);

            echo json_encode(["success" => true]);
            break;

        case 'PUT':
            parse_str($_SERVER['QUERY_STRING'], $query);
            $id = intval($query['id'] ?? 0);
            $input = json_decode(file_get_contents('php://input'), true);

            $nombre = trim($input['nombre'] ?? '');
            $apellido = trim($input['apellido'] ?? '');
            $telefono = trim($input['telefono'] ?? '');
            $correo = trim($input['correo'] ?? '');
            $password = $input['password'] ?? '';
            $rol_id = intval($input['rol_id'] ?? 0);
            $activo = isset($input['activo']) ? intval($input['activo']) : 1;
            $imagen = $input['imagen'] ?? '';

            if (!$id || !$nombre || !$correo || !$rol_id) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Faltan campos requeridos"]);
                exit;
            }

            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE empleados
                    SET nombre = :nombre, apellido = :apellido, telefono = :telefono, correo = :correo, password = :password,
                        rol_id = :rol_id, activo = :activo, imagen = :imagen
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':telefono' => $telefono,
                    ':correo' => $correo,
                    ':password' => $hashedPassword,
                    ':rol_id' => $rol_id,
                    ':activo' => $activo,
                    ':imagen' => $imagen,
                    ':id' => $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE empleados
                    SET nombre = :nombre, apellido = :apellido, telefono = :telefono, correo = :correo,
                        rol_id = :rol_id, activo = :activo, imagen = :imagen
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':telefono' => $telefono,
                    ':correo' => $correo,
                    ':rol_id' => $rol_id,
                    ':activo' => $activo,
                    ':imagen' => $imagen,
                    ':id' => $id
                ]);
            }

            echo json_encode(["success" => true]);
            break;

        case 'DELETE':
            parse_str($_SERVER['QUERY_STRING'], $query);
            $id = intval($query['id'] ?? 0);

            if (!$id) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "ID requerido"]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE empleados SET eliminado = 1 WHERE id = :id");
            $stmt->execute([':id' => $id]);

            echo json_encode(["success" => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Método no permitido"]);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error en el servidor: " . $e->getMessage()]);
}
