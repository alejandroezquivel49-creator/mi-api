<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'bd.php';
header('Content-Type: application/json; charset=UTF-8');

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Formato de datos inválido']);
    exit;
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

try {
    // Buscar usuario en tabla usuarios
    $sqlUsuario = "
        SELECT u.id, u.nombre, u.apellido, u.email, u.password, r.descripcion AS rol
        FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE u.email = :email
    ";
    $stmt = $pdo->prepare($sqlUsuario);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Verificar password
        if (password_verify($password, $usuario['password'])) {
            echo json_encode([
                'success' => true,
                'usuario' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'apellido' => $usuario['apellido'],
                    'email' => $usuario['email'],
                    'rol' => strtolower(trim($usuario['rol']))
                ]
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta para usuario']);
            exit;
        }
    } else {
        // Usuario no encontrado, buscar en empleados
        $sqlEmpleado = "
            SELECT e.id, e.nombre, e.apellido, e.correo, e.password, r.descripcion AS rol
            FROM empleados e
            JOIN roles r ON e.rol_id = r.id
            WHERE e.correo = :email AND e.activo = 1 AND e.eliminado = 0
        ";
        $stmt = $pdo->prepare($sqlEmpleado);
        $stmt->execute(['email' => $email]);
        $empleado = $stmt->fetch();

        if ($empleado) {
            if (password_verify($password, $empleado['password'])) {
                echo json_encode([
                    'success' => true,
                    'usuario' => [
                        'id' => $empleado['id'],
                        'nombre' => $empleado['nombre'],
                        'apellido' => $empleado['apellido'],
                        'email' => $empleado['correo'],
                        'rol' => strtolower(trim($empleado['rol']))
                    ]
                ]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta para empleado']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado en usuarios ni empleados']);
            exit;
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    exit;
}
