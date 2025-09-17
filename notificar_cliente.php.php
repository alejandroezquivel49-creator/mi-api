<?php
header("Content-Type: application/json; charset=UTF-8");
require "conexion.php"; // tu conexión PDO a la base de datos
require __DIR__ . '/vendor/autoload.php'; // Autoload de Twilio (composer require twilio/sdk)

use Twilio\Rest\Client;

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id_pedido'])) {
    echo json_encode(["error" => "Falta id_pedido"]);
    exit;
}

$id_pedido = $data['id_pedido'];

// 📌 1. Buscar datos del cliente
$stmt = $pdo->prepare("SELECT c.telefono, c.nombre 
                       FROM pedidos p
                       JOIN clientes c ON p.cliente_id = c.id
                       WHERE p.id = ?");
$stmt->execute([$id_pedido]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo json_encode(["error" => "Cliente no encontrado"]);
    exit;
}

$telefonoCliente = preg_replace('/\D/', '', $cliente['telefono']); // solo números
$nombreCliente = $cliente['nombre'];

// 📌 2. Configurar credenciales de Twilio (obtenidas desde https://www.twilio.com/console)
$account_sid = "TU_TWILIO_ACCOUNT_SID";
$auth_token = "TU_TWILIO_AUTH_TOKEN";
$twilio_whatsapp_number = "whatsapp:+14155238886"; // número de Twilio WhatsApp

try {
    $client = new Client($account_sid, $auth_token);

    // 📌 3. Mensaje a enviar
    $mensaje = "Hola $nombreCliente 👋, tu pedido #$id_pedido ya está listo para retirar. ¡Gracias por tu compra!";

    // 📌 4. Enviar por WhatsApp
    $client->messages->create(
        "whatsapp:+595$telefonoCliente", // número del cliente con código país
        [
            "from" => $twilio_whatsapp_number,
            "body" => $mensaje
        ]
    );

    echo json_encode(["success" => true, "mensaje" => "WhatsApp enviado"]);
} catch (Exception $e) {
    echo json_encode(["error" => "Error enviando WhatsApp: " . $e->getMessage()]);
}
