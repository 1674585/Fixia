<?php
    // ─────────────────────────────────────────────
    // Controlador: Detalle y edición de una tarea
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
    $tarea_id    = isset($_GET['tarea_id'])  ? (int)$_GET['tarea_id']  : 0;
    $orden_id    = isset($_GET['orden_id'])  ? (int)$_GET['orden_id']  : 0;
    $error       = null;

    if ($tarea_id === 0 || $orden_id === 0) {
        header("Location: index.php?action=misTareas");
        exit;
    }

    // ── POST: guardar cambios ──────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $estado      = $_POST['estado']      ?? '';
        $hora_inicio = $_POST['hora_inicio'] ?? '';
        $hora_fin    = $_POST['hora_fin']    ?? '';

        $estados_validos = ['pendiente', 'en_proceso', 'finalizada'];
        if (!in_array($estado, $estados_validos)) {
            $error = "Estado no válido.";
        } elseif ($hora_fin && $hora_inicio && $hora_fin < $hora_inicio) {
            $error = "La hora de finalización no puede ser anterior a la de inicio.";
        } else {
            $resultado = actualizarTarea($tarea_id, $mecanico_id, $taller_id, $estado, $hora_inicio, $hora_fin);

            if ($resultado['exito']) {
                // Si todas las tareas quedaron finalizadas, avisamos en la pantalla de la orden
                $param_lista = $resultado['orden_lista'] ? '&orden_lista=1' : '';
                header("Location: index.php?action=tareasOrden&orden_id={$orden_id}&guardada=1{$param_lista}");
                exit;
            } else {
                $error = $resultado['mensaje'];
            }
        }
    }

    // ── GET: cargar tarea ──────────────────────────
    $tarea = obtenerTareaPorId($tarea_id, $mecanico_id, $taller_id);

    if (!$tarea) {
        header("Location: index.php?action=misTareas");
        exit;
    }

    require_once __DIR__ . '/../vista/v_detallesTarea.php';
?>