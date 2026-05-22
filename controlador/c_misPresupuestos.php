<?php
    // ─────────────────────────────────────────────
    // Controlador: "Mis Presupuestos" (rol cliente)
    //   GET sin id  → listado de presupuestos pendientes
    //   GET con id  → detalle del presupuesto (tareas individuales)
    //   POST        → confirmar selección aceptar/rechazar
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=inicioSesion");
        exit;
    }

    if (($_SESSION['rol'] ?? '') !== 'cliente') {
        header("Location: index.php?action=home");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_misPresupuestos.php';

    $cliente_id = (int)$_SESSION['user_id'];
    $taller_id  = (int)$_SESSION['taller_id'];
    $orden_id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $mensaje_ok  = null;
    $mensaje_err = null;

    // ── POST: confirmar presupuesto ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $orden_id_post = isset($_POST['orden_id']) ? (int)$_POST['orden_id'] : 0;
        $aceptadas     = $_POST['aceptadas'] ?? [];

        if (!is_array($aceptadas)) {
            $aceptadas = [];
        }

        if ($orden_id_post <= 0) {
            $mensaje_err = "Orden no válida.";
        } else {
            $res = confirmarPresupuestoCliente($orden_id_post, $cliente_id, $taller_id, $aceptadas);

            if ($res['exito']) {
                header("Location: index.php?action=misPresupuestos&confirmado=1");
                exit;
            }
            $mensaje_err = $res['mensaje'];
            $orden_id    = $orden_id_post; // volver a mostrar el detalle
        }
    }

    if (isset($_GET['confirmado'])) {
        $mensaje_ok = "Presupuesto confirmado. Tu taller continuará con las tareas aceptadas.";
    }

    // ── GET ──
    if ($orden_id > 0) {
        $detalle = obtenerPresupuestoDetalle($orden_id, $cliente_id, $taller_id);
        if (!$detalle) {
            header("Location: index.php?action=misPresupuestos");
            exit;
        }
        $modo = 'detalle';
    } else {
        $presupuestos = obtenerPresupuestosPendientesCliente($cliente_id, $taller_id);
        $modo = 'listado';
    }

    require_once __DIR__ . '/../vista/v_misPresupuestos.php';
?>
