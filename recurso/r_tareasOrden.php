<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas de la Orden — Fixia</title>
    <link rel="stylesheet" type="text/css" href="/Fixia/style.css">
</head>
<body class="container">
    <header>
        <?php require __DIR__ . '/../controlador/c_menuSuperior.php'; ?>
    </header>
    <section id="contenido-principal">
        <?php require_once __DIR__ . '/../controlador/c_tareasOrden.php'; ?>
    </section>
    <?php require __DIR__ . '/../controlador/c_barraInferior.php'; ?>
</body>
</html>