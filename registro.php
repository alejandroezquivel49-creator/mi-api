<?php
// Solución: permitir el origen real de tu app
header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'cors.php';
require 'bd.php';

header('Content-Type: application/json');

// Leer datos JSON del body
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// Para debug (opcional)
//file_put_contents("debug_registro.txt", "Input: " . print_r($data, true) . "\n", FILE_APPEND);

if ($data === null) {
    echo json_encode(["success" => false, "message" => "JSON inválido o vacío"]);
    exit;
}

$nombre = trim($data["nombre"] ?? '');
$apellido = trim($data["apellido"] ?? '');
$email = trim($data["email"] ?? '');
$telefono = trim($data["telefono"] ?? '');
$password = $data["password"] ?? '';
$rol_id = 2; // Cliente fijo

if (!$nombre || !$apellido || !$email || !$telefono || !$password) {
    echo json_encode(["success" => false, "message" => "Faltan datos requeridos"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Correo electrónico inválido"]);
    exit;
}

try {
    // Validar email único
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $check->bindParam(":email", $email);
    $check->execute();

    if ($check->rowCount() > 0) {
        echo json_encode(["success" => false, "message" => "El correo ya está registrado"]);
        exit;
    }

    // Hashear contraseña
    $passwordHasheado = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, telefono, password, rol_id) VALUES (:nombre, :apellido, :email, :telefono, :password, :rol_id)");

    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":apellido", $apellido);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":telefono", $telefono);
    $stmt->bindParam(":password", $passwordHasheado);
    $stmt->bindParam(":rol_id", $rol_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Usuario registrado correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar usuario"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error en el servidor: " . $e->getMessage()]);
}
