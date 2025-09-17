<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pago cancelado</title>

<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #fafafa;
    text-align: center;
    padding: 50px;
  }
  .card {
    background: #fff;
    border-radius: 10px;
    padding: 30px;
    max-width: 500px;
    margin: auto;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
  }
  h1 {
    color: #e74c3c;
  }
  p {
    font-size: 16px;
    color: #555;
  }
  a {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 20px;
    background: #3498db;
    color: white;
    border-radius: 5px;
    text-decoration: none;
  }
  a:hover {
    background: #2980b9;
  }
</style>
</head>
<body>
  <div class="card">
    <h1>❌ Pago cancelado</h1>
    <p>Has cancelado el proceso de pago. Si esto fue un error, puedes intentarlo nuevamente.</p>
    <a href="https://tudominio.com">Volver al inicio</a>
  </div>
</body>
</html>
