<?php
    // ─────────────────────────────────────────────
    // Controlador: Listado de órdenes de trabajo
    // con filtros, ordenación y auto-asignación rápida
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=home");
        exit;
    }

    // Solo roles autorizados pueden ver las órdenes
    $roles_permitidos = ['ceo', 'jefe', 'recepcionista', 'mecanico'];
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        header("Location: index.php?action=home");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_ordenesTrabajo.php';

    $taller_id = (int)$_SESSION['taller_id'];
    $user_id   = (int)$_SESSION['user_id'];
    $rol       = $_SESSION['rol'];

    $mensaje_ok  = null;
    $mensaje_err = null;

    // ── POST: auto-asignación rápida (jefe / ceo) ───
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'autoasignar') {
        if (!in_array($rol, ['ceo', 'jefe'])) {
            $mensaje_err = "No tienes permiso para asignarte órdenes.";
        } else {
            $orden_id = isset($_POST['orden_id']) ? (int)$_POST['orden_id'] : 0;
            if ($orden_id <= 0) {
                $mensaje_err = "Orden no válida.";
            } else {
                $res = asignarOrdenAMecanico($orden_id, $user_id, $user_id, $taller_id);
                if ($res['exito']) {
                    // Conservar filtros activos al redirigir
                    $qs = $_GET;
                    $qs['action']    = 'ordenesTrabajo';
                    $qs['asignada']  = 1;
                    header("Location: index.php?" . http_build_query($qs));
                    exit;
                }
                $mensaje_err = $res['mensaje'];
            }
        }
    }

    if (isset($_GET['asignada'])) {
        $mensaje_ok = "Orden asignada correctamente.";
    }

    // ── Filtros ─────────────────────────────────────
    $filtros = [
        'estado'      => $_GET['estado']      ?? 'todos',
        'fecha_desde' => $_GET['fecha_desde'] ?? '',
        'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
        'asignacion'  => $_GET['asignacion']  ?? 'todos',
        'buscar'      => trim($_GET['buscar'] ?? ''),
    ];

    // Validar formato de fechas (Y-m-d)
    foreach (['fecha_desde', 'fecha_hasta'] as $f) {
        if ($filtros[$f] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros[$f])) {
            $filtros[$f] = '';
        }
    }

    $orden_col  = $_GET['orden']   ?? 'fecha';
    $orden_dir  = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

    $ordenes        = obtenerOrdenesConFiltros($taller_id, $filtros, $orden_col, $orden_dir);
    $usuarios_asign = obtenerUsuariosAsignables($taller_id);

    // Conservar el filtro de estado para compatibilidad con la vista existente
    $filtro_estado  = $filtros['estado'];

    require_once __DIR__ . '/../vista/v_ordenesTrabajo.php';
?>
