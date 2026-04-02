<?php
    // ─────────────────────────────────────────────
    // Controlador: Tareas de una orden asignadas
    // al mecánico en sesión
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=home");
        exit;
    }

    $roles_permitidos = ['ceo', 'jefe', 'recepcionista', 'mecanico'];
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        header("Location: index.php?action=home");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_misTareas.php';

    $mecanico_id = (int)$_SESSION['user_id'];
    $taller_id   = (int)$_SESSION['taller_id'];
    $orden_id    = isset($_GET['orden_id']) ? (int)$_GET['orden_id'] : 0;
    $error_orden = null;

    if ($orden_id === 0) {
        header("Location: index.php?action=misTareas");
        exit;
    }

    // ── POST: marcar orden como lista manualmente ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_lista'])) {
        $resultado = marcarOrdenComoLista($orden_id, $taller_id);
        if ($resultado['exito']) {
            header("Location: index.php?action=tareasOrden&orden_id={$orden_id}&orden_lista=1");
            exit;
        } else {
            $error_orden = $resultado['mensaje'];
        }
    }

    $tareas = obtenerTareasDeOrden($orden_id, $mecanico_id, $taller_id);

    // Si no hay tareas de este mecánico en esta orden, redirigir
    if (empty($tareas)) {
        header("Location: index.php?action=misTareas");
        exit;
    }

    // Datos de cabecera: los coge de la primera tarea (todos comparten orden/vehículo)
    $info_orden = $tareas[0];

    require_once __DIR__ . '/../vista/v_tareasOrden.php';
?>