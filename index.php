<?php
    $action = $_GET['action'] ?? 'home';

    switch ($action) {
        case 'home':
            require __DIR__ . '/recurso/r_home.php';
            break;

        default:
            echo "Acción no válida.";
    }