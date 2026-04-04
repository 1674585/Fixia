<?php
    // ─────────────────────────────────────────────
    // Controlador: Facturación — listado de órdenes
    // listas pendientes de cobro
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

    $taller_id   = (int)$_SESSION['taller_id'];
    $mensaje_ok  = null;
    $mensaje_err = null;

    if (isset($_GET['facturada'])) $mensaje_ok  = 'Pago confirmado correctamente. La orden ha sido facturada.';
    if (isset($_GET['error']))     $mensaje_err = htmlspecialchars($_GET['error']);

    $ordenes = obtenerOrdenesPendientesCobro($taller_id);

    require_once __DIR__ . '/../vista/v_facturacion.php';
?>
