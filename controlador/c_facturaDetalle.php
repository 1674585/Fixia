<?php
    // ─────────────────────────────────────────────
    // Controlador: Detalle de factura de una orden
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=home");
        exit;
    }

    $roles_permitidos = ['ceo', 'jefe', 'recepcionista'];
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        header("Location: index.php?action=home");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_facturacion.php';

    $taller_id = (int)$_SESSION['taller_id'];
    $orden_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($orden_id === 0) {
        header("Location: index.php?action=facturacion");
        exit;
    }

    $factura = obtenerDetalleFactura($orden_id, $taller_id);

    if (!$factura) {
        header("Location: index.php?action=facturacion");
        exit;
    }

    require_once __DIR__ . '/../vista/v_facturaDetalle.php';
?>