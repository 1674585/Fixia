<?php
// Si es una petición AJAX (POST con 'accion'), delega directamente al
// controlador sin emitir HTML: evita que el JSON llegue precedido del
// DOCTYPE y demás marcado de la vista.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion'])) {
    require __DIR__ . '/../controlador/c_ia.php';
    return;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IA — Fixia</title>
    <link rel="stylesheet" type="text/css" href="/Fixia/style.css">
</head>
<body class="container">
    <header>
        <?php require __DIR__ . '/../controlador/c_menuSuperior.php'; ?>
    </header>
    <section id="contenido-principal">
        <?php require_once __DIR__ . '/../controlador/c_ia.php'; ?>
    </section>
    <?php require __DIR__ . '/../controlador/c_barraInferior.php'; ?>
</body>
</html>
