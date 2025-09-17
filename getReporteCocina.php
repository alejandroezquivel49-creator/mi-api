<?php
header('Content-Type: application/json');
require 'cors.php';
require 'bd.php'; // conexión PDO en $pdo

$rango = $_GET['rango'] ?? 'dia';

// Construir condición según rango
if ($rango == 'dia') {
    $condicion = "p.fecha_pedido = CURDATE()";
} elseif ($rango == 'semana') {
    $condicion = "p.fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($rango == 'mes') {
    $condicion = "MONTH(p.fecha_pedido) = MONTH(CURDATE()) AND YEAR(p.fecha_pedido) = YEAR(CURDATE())";
} else {
    echo json_encode(['success'=>false,'message'=>'Rango no válido']);
    exit;
}

try {
    // Total de pedidos
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total_pedidos FROM pedidos p WHERE $condicion");
    $stmtTotal->execute();
    $totalPedidos = $stmtTotal->fetchColumn();

    // Detalle de productos
    $stmtDetalle = $pdo->prepare("
        SELECT pr.nombre, SUM(d.cantidad) AS cantidad
        FROM detalle_pedido d
        JOIN pedidos p ON p.id = d.id_pedido
        JOIN productos pr ON pr.id = d.id_producto
        WHERE $condicion
        GROUP BY pr.nombre
    ");
    $stmtDetalle->execute();
    $detalleProductos = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_pedidos' => intval($totalPedidos),
            'detalle_productos' => $detalleProductos
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false, 'message'=>'Error al obtener detalle de productos: '.$e->getMessage()]);
}
