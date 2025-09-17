<?php
require 'cors.php';
require 'bd.php';

header("Content-Type: application/json");

try {
    $stmt = $pdo->prepare("SELECT id, descripcion FROM roles WHERE activo = 1");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($roles);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error en el servidor: " . $e->getMessage()]);
}
