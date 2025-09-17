<?php
header('Content-Type: application/json');
require 'cors.php';
require 'bd.php';

try {
    // 1. Estados disponibles
    $stmt = $pdo->query("SELECT id, descripcion FROM estados_pedidos");
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($estados as &$estado) {
        $estado['nombre'] = $estado['descripcion'];
        unset($estado['descripcion']);
    }

    // 2. Pedidos con detalle filtrando solo Postres y Comidas
    $sqlPedidos = "
        SELECT 
            p.id AS pedido_id,
            p.estado_id,
            e.descripcion AS estado,
            u.nombre AS cliente,
            pr.id AS producto_id,
            pr.nombre AS producto_nombre,
            pr.categoria_id,
            c.descripcion AS categoria_nombre,
            dp.cantidad,
            dp.precio_venta
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN detalle_pedido dp ON p.id = dp.id_pedido
        LEFT JOIN productos pr ON dp.id_producto = pr.id
        LEFT JOIN categorias c ON pr.categoria_id = c.id
        LEFT JOIN estados_pedidos e ON p.estado_id = e.id
        WHERE p.eliminado = 0
          AND c.descripcion IN ('Postres', 'Comidas')
        ORDER BY p.id DESC
    ";
    $stmtPedidos = $pdo->query($sqlPedidos);
    $rows = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar pedidos
    $pedidos = [];
    foreach ($rows as $row) {
        $id = $row['pedido_id'];
        if (!isset($pedidos[$id])) {
            $pedidos[$id] = [
                'id' => $id,
                'estado_id' => $row['estado_id'],
                'estado' => $row['estado'],
                'cliente' => $row['cliente'],
                'detalle' => [],
                'total' => 0
            ];
        }

        $producto = [
            'id_producto' => $row['producto_id'],
            'nombre' => $row['producto_nombre'],
            'categoria_id' => $row['categoria_id'],
            'categoria_nombre' => $row['categoria_nombre'],
            'cantidad' => (int)$row['cantidad'],
            'precio' => (float)$row['precio_venta']
        ];

        $pedidos[$id]['detalle'][] = $producto;
        $pedidos[$id]['total'] += $producto['precio'] * $producto['cantidad'];
    }

    echo json_encode([
        'success' => true,
        'estados' => $estados,
        'pedidos' => array_values($pedidos)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error al obtener datos: " . $e->getMessage()
    ]);
}
