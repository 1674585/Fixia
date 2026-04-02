<?php
    // ─────────────────────────────────────────────
    // Controlador: Mis tareas — listado de órdenes
    // Solo accesible por mecánicos (y superiores)
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

    $ordenes = obtenerOrdenesPorMecanico($mecanico_id, $taller_id);

    require_once __DIR__ . '/../vista/v_misTareas.php';
    ?>