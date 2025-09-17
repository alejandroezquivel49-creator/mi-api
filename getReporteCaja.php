<?php
header('Content-Type: application/json');

require 'cors.php';
require 'bd.php'; // conexión PDO en $pdo

// Obtener el rango
$rango = $_GET['rango'] ?? 'dia';

// Construir consulta según rango
if ($rango == 'dia') {
    $sql = "SELECT COUNT(*) AS total_pedidos, SUM(total) AS total_pagado
            FROM pedidos
            WHERE pagado = 1 AND fecha_pedido = CURDATE()";
} elseif ($rango == 'semana') {
    $sql = "SELECT COUNT(*) AS total_pedidos, SUM(total) AS total_pagado
            FROM pedidos
            WHERE pagado = 1 AND fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($rango == 'mes') {
    $sql = "SELECT COUNT(*) AS total_pedidos, SUM(total) AS total_pagado
            FROM pedidos
            WHERE pagado = 1
              AND MONTH(fecha_pedido) = MONTH(CURDATE())
              AND YEAR(fecha_pedido) = YEAR(CURDATE())";
} else {
    echo json_encode(['success' => false, 'message' => 'Rango no válido']);
    exit;
}

// Ejecutar consulta usando PDO
try {
    $stmt = $pdo->query($sql);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la consulta: ' . $e->getMessage()]);
}
