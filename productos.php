<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

$method = $_SERVER['REQUEST_METHOD'];
$conexion = new mysqli("localhost", "root", "", "recopedi");

if ($conexion->connect_error) {
    die(json_encode(["success" => false, "message" => "Error de conexión: " . $conexion->connect_error]));
}

switch ($method) {
    /** ====== LISTAR PRODUCTOS ====== */
    case 'GET':
        $result = $conexion->query("SELECT * FROM productos");
        $productos = [];

        while ($row = $result->fetch_assoc()) {
            $row['imagen_url'] = !empty($row['imagen'])
                ? "uploads/productos/" . $row['imagen']
                : null;
            $productos[] = $row;
        }

        echo json_encode(["success" => true, "productos" => $productos]);
        break;

    /** ====== CREAR PRODUCTO ====== */
    case 'POST':
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $precio_unitario = $_POST['precio_unitario'] ?? 0;
        $precio_venta = $_POST['precio_venta'] ?? 0;
        $categoria_id = $_POST['categoria_id'] ?? null;

        $imagenNombre = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array(strtolower($ext), $permitidas)) {
                $imagenNombre = uniqid() . "." . $ext;
                $destino = __DIR__ . "/uploads/productos/" . $imagenNombre;

                if (!is_dir(__DIR__ . "/uploads/productos")) {
                    mkdir(__DIR__ . "/uploads/productos", 0777, true);
                }

                move_uploaded_file($_FILES['imagen']['tmp_name'], $destino);
            }
        }

        // Si no hay imagen, guardamos cadena vacía
        if ($imagenNombre === null) {
            $imagenNombre = '';
        }

        $stmt = $conexion->prepare(
            "INSERT INTO productos (nombre, descripcion, precio_unitario, precio_venta, categoria_id, imagen) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssddis", $nombre, $descripcion, $precio_unitario, $precio_venta, $categoria_id, $imagenNombre);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Producto creado correctamente"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al crear producto: " . $stmt->error]);
        }
        break;

    /** ====== ACTUALIZAR PRODUCTO ====== */
    case 'PUT':
        parse_str(file_get_contents("php://input"), $_PUT);
        $id = $_GET['id'] ?? null;

        if (!$id) {
            echo json_encode(["success" => false, "message" => "ID requerido"]);
            exit;
        }

        $nombre = $_PUT['nombre'] ?? '';
        $descripcion = $_PUT['descripcion'] ?? '';
        $precio_unitario = $_PUT['precio_unitario'] ?? 0;
        $precio_venta = $_PUT['precio_venta'] ?? 0;
        $categoria_id = $_PUT['categoria_id'] ?? null;

        $stmt = $conexion->prepare(
            "UPDATE productos SET nombre=?, descripcion=?, precio_unitario=?, precio_venta=?, categoria_id=? WHERE id=?"
        );
        $stmt->bind_param("ssddii", $nombre, $descripcion, $precio_unitario, $precio_venta, $categoria_id, $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Producto actualizado"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
        }
        break;

    /** ====== ELIMINAR PRODUCTO ====== */
    case 'DELETE':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            echo json_encode(["success" => false, "message" => "ID requerido"]);
            exit;
        }

        // Borrar imagen del servidor si existe
        $result = $conexion->query("SELECT imagen FROM productos WHERE id=$id");
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['imagen']) && file_exists(__DIR__ . "/uploads/productos/" . $row['imagen'])) {
                unlink(__DIR__ . "/uploads/productos/" . $row['imagen']);
            }
        }

        if ($conexion->query("DELETE FROM productos WHERE id=$id")) {
            echo json_encode(["success" => true, "message" => "Producto eliminado"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al eliminar: " . $conexion->error]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Método no soportado"]);
        break;
}

$conexion->close();
