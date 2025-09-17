<?php
require 'cors.php';
require 'bd.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "No se recibieron datos válidos"]);
        exit;
    }

    $usuario_id = intval($data['usuario_id'] ?? 0);
    $fecha_pedido = $data['fecha_pedido'] ?? date('Y-m-d');
    $hora_pedido = $data['hora_pedido'] ?? date('H:i:s');
    $productos = $data['productos'] ?? [];
    $pagado = intval($data['pagado'] ?? 0);

    if (empty($usuario_id) || empty($productos)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Faltan campos obligatorios"]);
        exit;
    }

    // Calcular total
    $total = 0;
    foreach ($productos as $prod) {
        $precio_venta = floatval($prod['precio_venta'] ?? 0);
        $cantidad = intval($prod['cantidad'] ?? 0);
        if ($precio_venta <= 0 || $cantidad <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Producto o cantidad inválidos"]);
            exit;
        }
        $total += $precio_venta * $cantidad;
    }

    $estado_id = 1; // Pendiente
    $hora_entrada_cocina = date('Y-m-d H:i:s');

    // Insertar pedido
    $stmt = $pdo->prepare("INSERT INTO pedidos 
        (usuario_id, estado_id, fecha_pedido, hora_pedido, hora_entrada_cocina, total, pagado, eliminado)
        VALUES (:usuario_id, :estado_id, :fecha_pedido, :hora_pedido, :hora_entrada_cocina, :total, :pagado, 0)");

    $stmt->execute([
        ':usuario_id' => $usuario_id,
        ':estado_id' => $estado_id,
        ':fecha_pedido' => $fecha_pedido,
        ':hora_pedido' => $hora_pedido,
        ':hora_entrada_cocina' => $hora_entrada_cocina,
        ':total' => $total,
        ':pagado' => $pagado
    ]);

    $pedido_id = $pdo->lastInsertId();

    // Insertar detalle
    $stmtDetalle = $pdo->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, precio_venta) 
                                  VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario, :precio_venta)");

    foreach ($productos as $prod) {
        $stmtDetalle->execute([
            ':id_pedido' => $pedido_id,
            ':id_producto' => intval($prod['id_producto']),
            ':cantidad' => intval($prod['cantidad']),
            ':precio_unitario' => floatval($prod['precio_unitario'] ?? 0),
            ':precio_venta' => floatval($prod['precio_venta'] ?? 0)
        ]);
    }

    echo json_encode(["success" => true, "pedido_id" => $pedido_id]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error en el servidor: " . $e->getMessage()]);
}
