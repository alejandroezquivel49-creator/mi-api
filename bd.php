<?php
$host = "sql201.infinityfree.com";
$user = "if0_39929555";
$password = "fbyRgGhqxolvny";
$dbname = "if0_39929555_recopedi";

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die(json_encode(["success" => false, "message" => "Conexión fallida: " . $e->getMessage()]));
}
