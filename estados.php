<?php
header('Content-Type: application/json');
require 'cors.php';
require 'bd.php';

try {
    $stmt = $pdo->query("SELECT id, descripcion FROM estados_pedidos");
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cambiar 'descripcion' a 'nombre' para que el frontend use esa propiedad
    foreach ($estados as &$estado) {
        $estado['nombre'] = $estado['descripcion'];
        unset($estado['descripcion']);
    }

    echo json_encode(['success' => true, 'estados' => $estados]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error al obtener estados: " . $e->getMessage()]);
}
