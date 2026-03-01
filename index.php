<?php
    session_start(); // Fundamental para saber quién es el usuario

    $action = $_GET['action'] ?? 'home';

    switch ($action) {
        case 'home':
            require __DIR__ . '/recurso/r_home.php';
            break;
        
        case 'inicioSesion':
            require __DIR__ . '/recurso/r_inicioSesion.php';
            break;

        case 'registroUsuario':
            require __DIR__ . '/recurso/r_registroUsuario.php';
            break;

        case 'tareasPendientes':
            if (!isset($_SESSION['rol'])) {
                header("Location: index.php?action=inicioSesion");
                exit;
            }

            require __DIR__ . '/recurso/r_tareasPendientes.php';
            break;

        case 'cerrarSesion':
            session_destroy();
            header("Location: index.php?action=home");
            break;

        default:
            echo "Acción no válida.";
    }
?>