<?php
    // ─────────────────────────────────────────────
    // Controlador: Confirmar pago de una orden
    // Solo acepta POST. Redirige siempre al acabar.
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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: index.php?action=facturacion");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_facturacion.php';

    $taller_id = (int)$_SESSION['taller_id'];
    $orden_id  = isset($_POST['orden_id']) ? (int)$_POST['orden_id'] : 0;
    $origen    = $_POST['origen'] ?? 'listado'; // 'detalle' o 'listado'

    if ($orden_id === 0) {
        header("Location: index.php?action=facturacion");
        exit;
    }

    $resultado = confirmarPagoOrden($orden_id, $taller_id);

    if ($resultado['exito']) {
        // Si venía del detalle, volvemos al detalle (ya mostrará estado facturado)
        if ($origen === 'detalle') {
            header("Location: index.php?action=facturaDetalle&id={$orden_id}&facturada=1");
        } else {
            header("Location: index.php?action=facturacion&facturada=1");
        }
    } else {
        $msg = urlencode($resultado['mensaje']);
        header("Location: index.php?action=facturacion&error={$msg}");
    }
    exit;
?>