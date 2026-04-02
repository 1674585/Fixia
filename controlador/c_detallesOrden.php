<?php
    // ─────────────────────────────────────────────
    // Controlador: Detalle y edición de una tarea
    // Incluye gestión de repuestos consumidos
    // ─────────────────name───────────────────────────
    
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
    $tarea_id    = isset($_GET['tarea_id']) ? (int)$_GET['tarea_id'] : 0;
    $orden_id    = isset($_GET['orden_id']) ? (int)$_GET['orden_id'] : 0;
    $error       = null;
    $exito       = null;

    if ($tarea_id === 0 || $orden_id === 0) {
        header("Location: index.php?action=misTareas");
        exit;
    }

    // ── POST: distintas acciones ───────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        // ── 1. Guardar estado + horas de la tarea ──
        if ($accion === 'guardar_tarea') {
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
                    $param_lista = $resultado['orden_lista'] ? '&orden_lista=1' : '';
                    header("Location: index.php?action=tareasOrden&orden_id={$orden_id}&guardada=1{$param_lista}");
                    exit;
                } else {
                    $error = $resultado['mensaje'];
                }
            }
        }

        // ── 2. Añadir repuesto ──────────────────────
        elseif ($accion === 'añadir_repuesto') {
            $producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
            $cantidad    = isset($_POST['cantidad'])    ? (int)$_POST['cantidad']    : 0;

            if ($producto_id === 0) {
                $error = "Debes seleccionar un producto.";
            } else {
                $resultado = añadirRepuestoATarea($tarea_id, $mecanico_id, $taller_id, $producto_id, $cantidad);
                if ($resultado['exito']) {
                    $exito = $resultado['mensaje'];
                } else {
                    $error = $resultado['mensaje'];
                }
            }
        }

        // ── 3. Eliminar repuesto ────────────────────
        elseif ($accion === 'eliminar_repuesto') {
            $repuesto_id = isset($_POST['repuesto_id']) ? (int)$_POST['repuesto_id'] : 0;

            if ($repuesto_id > 0) {
                $resultado = eliminarRepuestoDeTarea($repuesto_id, $mecanico_id, $taller_id);
                if ($resultado['exito']) {
                    $exito = $resultado['mensaje'];
                } else {
                    $error = $resultado['mensaje'];
                }
            }
        }
    }

    // ── Cargar datos (siempre frescos tras el POST) ─
    $tarea     = obtenerTareaPorId($tarea_id, $mecanico_id, $taller_id);
    $repuestos = obtenerRepuestosDeTarea($tarea_id);

    if (!$tarea) {
        header("Location: index.php?action=misTareas");
        exit;
    }

    // Coste total de materiales
    $coste_repuestos = array_reduce($repuestos, fn($carry, $r) => $carry + (float)$r['subtotal'], 0.0);

    // Coste en horas: tarifa_hora_base del taller (guardada en sesión al login, o default 45€)
    $tarifa_hora = (float)($_SESSION['tarifa_hora_base'] ?? 45.00);
    $coste_horas = $tarea['duracion_real_minutos']
        ? round(($tarea['duracion_real_minutos'] / 60) * $tarifa_hora, 2)
        : null;

    // Coste total de la tarea (horas + materiales)
    $coste_total = $coste_horas !== null ? round($coste_horas + $coste_repuestos, 2) : null;

    require_once __DIR__ . '/../vista/v_detallesTarea.php';
?>