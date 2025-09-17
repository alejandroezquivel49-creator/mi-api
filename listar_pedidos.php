
<?php
header('Access-Control-Allow-Origin: *');  // PERMITE peticiones desde cualquier origen
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include 'db.php';


try {
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
    $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;

    if (!$fecha_inicio || !$fecha_fin) {
        echo json_encode(['error' => 'Faltan parámetros de fecha']);
        exit;
    }

    $sql = "
        SELECT 
            pe.id AS pedido_id,
            pe.fecha_pedido,
            pe.hora_pedido,
            pe.estado,
            u.nombre AS cliente,
            d.nombre AS producto,
            dv.cantidad,
            dv.precio_venta,
            (dv.cantidad * dv.precio_venta) AS total_item
        FROM pedidos pe
        INNER JOIN usuarios u ON pe.cliente_id = u.id
        INNER JOIN pedido_detalle dv ON pe.id = dv.pedido_id
        INNER JOIN productos d ON dv.producto_id = d.id
        WHERE DATE(pe.fecha_pedido) BETWEEN :fecha_inicio AND :fecha_fin
        ORDER BY pe.fecha_pedido DESC, pe.hora_pedido DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();

    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($pedidos);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error al obtener pedidos',
        'message' => $e->getMessage()
    ]);
}
?>
