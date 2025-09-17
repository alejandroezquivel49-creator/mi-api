<?php
require 'cors.php'; // que envíe las cabeceras CORS y maneje OPTIONS
require 'bd.php';   // conexión $pdo PDO

header("Content-Type: application/json");

try {
    $stmt = $pdo->prepare("SELECT id, descripcion FROM categorias WHERE eliminado = 0");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];

    foreach ($rows as $row) {
        $data[] = [
            "id" => $row["id"],
            "nombre" => $row["descripcion"], // para mantener compatibilidad con frontend
        ];
    }

    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error en el servidor: " . $e->getMessage()
    ]);
}
