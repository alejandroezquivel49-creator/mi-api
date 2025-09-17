<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'cors.php';
require 'bd.php'; // conexión PDO en $pdo

$method = $_SERVER['REQUEST_METHOD'];
$inputJson = file_get_contents('php://input');
$input = json_decode($inputJson, true);

try {
    switch ($method) {
        // ================== OBTENER PEDIDOS ==================
        case 'GET':
            $estado_id = isset($_GET['estado_id']) ? intval($_GET['estado_id']) : null;
            $usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : null;
            $pagado = isset($_GET['pagado']) ? intval($_GET['pagado']) : null;
            $facturado = isset($_GET['facturado']) ? intval($_GET['facturado']) : null;

            $sql = "SELECT p.id, p.usuario_id, u.nombre AS usuario_nombre, p.estado_id, p.fecha_pedido, p.hora_pedido, 
                           p.total, p.pagado, p.facturado,
                           e.descripcion AS estado_descripcion
                    FROM pedidos p
                    LEFT JOIN estados_pedidos e ON p.estado_id = e.id
                    LEFT JOIN usuarios u ON p.usuario_id = u.id
                    WHERE p.eliminado = 0";

            $conds = [];
            $params = [];
            if ($estado_id !== null) { $conds[] = "p.estado_id = :estado_id"; $params[':estado_id']=$estado_id; }
            if ($usuario_id !== null) { $conds[] = "p.usuario_id = :usuario_id"; $params[':usuario_id']=$usuario_id; }
            if ($pagado !== null) { $conds[] = "p.pagado = :pagado"; $params[':pagado']=$pagado; }
            if ($facturado !== null) { $conds[] = "p.facturado = :facturado"; $params[':facturado']=$facturado; }

            if(count($conds) > 0){ $sql .= " AND ".implode(' AND ',$conds); }

            $sql .= " ORDER BY p.fecha_pedido DESC, p.hora_pedido DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener detalle de cada pedido
            foreach($pedidos as &$pedido){
                $stmtDet = $pdo->prepare("
                    SELECT dp.id, dp.id_producto, dp.cantidad, dp.pagado, dp.precio_unitario, dp.precio_venta, pr.nombre, pr.categoria_id
                    FROM detalle_pedido dp
                    JOIN productos pr ON dp.id_producto = pr.id
                    WHERE dp.id_pedido = ?
                ");
                $stmtDet->execute([$pedido['id']]);
                $pedido['detalle'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
            }

            echo json_encode(['success'=>true,'pedidos'=>$pedidos]);
            break;

        // ================== CREAR PEDIDO ==================
        case 'POST':
            if(!$input || !isset($input['usuario_id'],$input['productos']) || !is_array($input['productos'])){
                http_response_code(400);
                echo json_encode(['success'=>false,'message'=>'Datos incompletos']);
                exit;
            }

            $usuario_id = intval($input['usuario_id']);
            $productos = $input['productos'];
            $pagado = isset($input['pagado']) ? intval($input['pagado']) : 0;
            $facturado = isset($input['facturado']) ? intval($input['facturado']) : 0;
            $fecha_pedido = $input['fecha_pedido'] ?? date('Y-m-d');
            $hora_pedido = $input['hora_pedido'] ?? date('H:i:s');

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, estado_id, fecha_pedido, hora_pedido, total, eliminado, pagado, facturado) VALUES (?, 1, ?, ?, 0, 0, ?, ?)");
                $stmt->execute([$usuario_id, $fecha_pedido, $hora_pedido, $pagado, $facturado]);
                $pedido_id = $pdo->lastInsertId();

                $total = 0;
                $stmtDet = $pdo->prepare("INSERT INTO detalle_pedido (id_pedido,id_producto,cantidad,pagado,precio_unitario,precio_venta) VALUES (?,?,?,?,?,?)");
                foreach($productos as $prod){
                    $stmtDet->execute([
                        $pedido_id,
                        intval($prod['id_producto']),
                        intval($prod['cantidad']),
                        $pagado,
                        floatval($prod['precio_unitario']??0),
                        floatval($prod['precio_venta']??0)
                    ]);
                    $total += floatval($prod['precio_venta']??0) * intval($prod['cantidad']);
                }

                $stmtUpd = $pdo->prepare("UPDATE pedidos SET total=? WHERE id=?");
                $stmtUpd->execute([$total,$pedido_id]);

                $pdo->commit();
                echo json_encode(['success'=>true,'pedido_id'=>$pedido_id]);
            } catch(Exception $e){
                if($pdo->inTransaction()) $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
            }
            break;

        // ================== ACTUALIZAR PEDIDO ==================
        case 'PUT':
            parse_str($_SERVER['QUERY_STRING'],$query);
            $pedido_id = intval($query['id']??0);
            if(!$pedido_id || !$input){
                http_response_code(400);
                echo json_encode(['success'=>false,'message'=>'ID o datos inválidos']);
                exit;
            }

            $pdo->beginTransaction();
            try{
                $fields=[];$params=[];
                $posibles=['estado_id','total','fecha_pedido','hora_pedido','eliminado','pagado','facturado'];
                foreach($posibles as $campo){
                    if(isset($input[$campo])){
                        $fields[]="$campo=:$campo";
                        $params[":$campo"]=$input[$campo];
                    }
                }

                if(count($fields)>0){
                    $params[':id']=$pedido_id;
                    $stmt=$pdo->prepare("UPDATE pedidos SET ".implode(',',$fields)." WHERE id=:id");
                    $stmt->execute($params);
                }

                if(isset($input['productos']) && is_array($input['productos'])){
                    $stmtDel=$pdo->prepare("DELETE FROM detalle_pedido WHERE id_pedido=?");
                    $stmtDel->execute([$pedido_id]);
                    $total=0;
                    $stmtDet=$pdo->prepare("INSERT INTO detalle_pedido (id_pedido,id_producto,cantidad,pagado,precio_unitario,precio_venta) VALUES (?,?,?,?,?,?)");
                    $pagadoDetalle=isset($input['pagado']) ? intval($input['pagado']):0;
                    foreach($input['productos'] as $prod){
                        $stmtDet->execute([
                            $pedido_id,
                            intval($prod['id_producto']),
                            intval($prod['cantidad']),
                            $pagadoDetalle,
                            floatval($prod['precio_unitario']??0),
                            floatval($prod['precio_venta']??0)
                        ]);
                        $total+= floatval($prod['precio_venta']??0)* intval($prod['cantidad']);
                    }
                    $stmtUpd=$pdo->prepare("UPDATE pedidos SET total=? WHERE id=?");
                    $stmtUpd->execute([$total,$pedido_id]);
                }

                $pdo->commit();
                echo json_encode(['success'=>true,'message'=>'Pedido actualizado']);
            } catch(Exception $e){
                if($pdo->inTransaction()) $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
            }
            break;

        // ================== ELIMINAR PEDIDO ==================
        case 'DELETE':
            parse_str($_SERVER['QUERY_STRING'],$query);
            $pedido_id=intval($query['id']??0);
            if(!$pedido_id){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID requerido']); exit;}
            $stmt=$pdo->prepare("UPDATE pedidos SET eliminado=1 WHERE id=?");
            $stmt->execute([$pedido_id]);
            echo json_encode(['success'=>true,'message'=>'Pedido eliminado']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Método no permitido']);
    }
} catch(Exception $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
