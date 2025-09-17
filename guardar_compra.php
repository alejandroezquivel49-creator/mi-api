<?php
header('Content-Type: application/json');
require 'cors.php';
require 'bd.php';

// Recibir datos JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
if (!isset($data['fecha'], $data['monto'], $data['descripcion'], $data['proveedor'], $data['telefono'], $data['cantidad'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

try {
    // Insertar la compra en la tabla compras
    $stmt = $pdo->prepare("
        INSERT INTO compras (fecha, monto, descripcion, proveedor, telefono, cantidad) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['fecha'],
        $data['monto'],
        $data['descripcion'],
        $data['proveedor'],
        $data['telefono'],
        $data['cantidad']
    ]);

    $compra_id = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "compra_id" => $compra_id,
        "message" => "Compra registrada correctamente"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al guardar la compra: " . $e->getMessage()
    ]);
}
