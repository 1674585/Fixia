<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="/./style.css">
        <script type="text/javascript" src="/js/fetch.js"></script>
    </head>

    <body class="container">
        <header>
            <?php require __DIR__. '/../controlador/c_menuSuperior.php'; ?>
        </header>
        <section id="blocllista">
            <?php require_once __DIR__ . '/../controlador/c_registroUsuario.php'; ?>
        </section>
        
        <?php require __DIR__. '/../controlador/c_barraInferior.php'; ?>
    </body>
    
</html>