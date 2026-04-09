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

        // ── NUEVAS RUTAS ──────────────────────────────
        case 'asignarOrden':
            require __DIR__ . '/recurso/r_asignarOrden.php';
            break;

        // ── MIS TAREAS ────────────────────────────────
        case 'misTareas':
            require __DIR__ . '/recurso/r_misTareas.php';
            break;

        case 'tareasOrden':
            require __DIR__ . '/recurso/r_tareasOrden.php';
            break;

        case 'detallesTarea':
            require __DIR__ . '/recurso/r_detallesTarea.php';
            break;

        case 'buscarProductosTarea':
            require __DIR__ . '/controlador/c_buscarProductosTarea.php';
            break;

        // ── STOCK ─────────────────────────────────────
        case 'stock':
            require __DIR__ . '/recurso/r_stock.php';
            break;

        case 'stockFormulario':
            require __DIR__ . '/recurso/r_stockFormulario.php';
            break;

        // ── FACTURACIÓN ───────────────────────────────
        case 'facturacion':
            require __DIR__ . '/recurso/r_facturacion.php';
            break;

        case 'facturaDetalle':
            require __DIR__ . '/recurso/r_facturaDetalle.php';
            break;

        case 'confirmarPago':
            require __DIR__ . '/recurso/r_confirmarPago.php';
            break;

        // ── REPORTES ──────────────────────────────────
        case 'reportes':
            require __DIR__ . '/recurso/r_reportes.php';
            break;

        // ── USUARIOS ──────────────────────────────────
        case 'usuarios':
            require __DIR__ . '/recurso/r_usuarios.php';
            break;

        case 'usuariosFormulario':
            require __DIR__ . '/recurso/r_usuariosFormulario.php';
            break;

        case 'cambiarPassword':
            require __DIR__ . '/recurso/r_cambiarPassword.php';
            break;

        default:
            echo "Acción no válida.";
    }
?>