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

        case 'cerrarSesion':
            session_destroy();
            header("Location: index.php?action=home");
            break;

        case 'misVehiculos':
            require __DIR__ . '/recurso/r_misVehiculos.php';
            break;

        case 'detallesVehiculo':
            require __DIR__ . '/recurso/r_detallesVehiculo.php';
            break;
        
        case 'registrarVehiculo':
            require __DIR__ . '/recurso/r_registrarVehiculo.php';
            break;

        case 'buscarClientes':
            require __DIR__ . '/controlador/c_buscarClientes.php';
            break;

        case 'obtenerModelos':
            require __DIR__ . '/controlador/c_obtenerModelosPorMarca.php';
            break;

        case 'crearOrden':
            require __DIR__ . '/recurso/r_crearOrden.php';
            break;

        case 'obtenerSubgrupos':
            require __DIR__ . '/controlador/c_obtenerSubgrupos.php';
            break;
        
        case 'detallesOrden':
            require __DIR__ . '/recurso/r_detallesOrden.php';
            break;  
        
        case 'ordenesTrabajo':
            require __DIR__ . '/recurso/r_ordenesTrabajo.php';
            break;

        case 'asignarOrden':
            require __DIR__ . '/recurso/r_asignarOrden.php';
            break;

        default:
            echo "Acción no válida.";
    }
?>