<?php
    // ─────────────────────────────────────────────
    // Controlador: Tareas de una orden de trabajo
    //   · ceo / jefe → ven TODAS las tareas (vista supervisor)
    //   · mecánico / recepcionista → solo las suyas
    // Validación: la pantalla rechaza el acceso si un mecánico
    // intenta ver tareas de otra persona.
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

    $user_id      = (int)$_SESSION['user_id'];
    $taller_id    = (int)$_SESSION['taller_id'];
    $rol          = $_SESSION['rol'];
    $orden_id     = isset($_GET['orden_id']) ? (int)$_GET['orden_id'] : 0;
    $error_orden  = null;

    // ¿El usuario tiene permiso de supervisor para ver tareas ajenas?
    $es_supervisor = in_array($rol, ['ceo', 'jefe']);

    if ($orden_id === 0) {
        header("Location: index.php?action=" . ($es_supervisor ? 'ordenesTrabajo' : 'misTareas'));
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

    // Carga de tareas según permisos
    if ($es_supervisor) {
        $tareas = obtenerTareasOrdenCompleta($orden_id, $taller_id);
    } else {
        // Mecánico / recepcionista: solo sus propias tareas
        $tareas = obtenerTareasDeOrden($orden_id, $user_id, $taller_id);
    }

    if (empty($tareas)) {
        // Supervisor: orden inexistente o sin tareas
        if ($es_supervisor) {
            header("Location: index.php?action=ordenesTrabajo");
            exit;
        }
        // Mecánico intentando ver tareas que no son suyas
        header("Location: index.php?action=misTareas");
        exit;
    }

    // Datos de cabecera: los coge de la primera tarea (todos comparten orden/vehículo)
    $info_orden = $tareas[0];

    require_once __DIR__ . '/../vista/v_tareasOrden.php';
?>
