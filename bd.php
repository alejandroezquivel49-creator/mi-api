<?php
// db.php – Conexión a MySQL usando variables de entorno

// Tomamos las variables de entorno, si no existen usamos valores por defecto
$host = getenv("DB_HOST") ?: "DOMINIO_PROXY_TCP_FERROCARRIL";
$user = getenv("DB_USER") ?: "raíz";
$pass = getenv("DB_PASS") ?: "yHiysFSKSfBaMRyXTjSSnIQPiZPfmDoQ";
$db   = getenv("DB_NAME") ?: "ferrocarril";
$port = getenv("DB_PORT") ?: 3306;

// Crear la conexión
$conn = new mysqli($host, $user, $pass, $db, $port);

// Revisar errores
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Opcional: establecer el charset a UTF-8
$conn->set_charset("utf8");

?>
