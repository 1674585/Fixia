<?php
    // ─────────────────────────────────────────────
    // Controlador: Listado de órdenes de trabajo
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

    $taller_id = $_SESSION['taller_id'];
    $ordenes   = obtenerTodasLasOrdenes($taller_id);

    // Filtro de estado (opcional, desde GET)
    $filtro_estado = $_GET['estado'] ?? 'todos';
    if ($filtro_estado !== 'todos') {
        $ordenes = array_filter($ordenes, fn($o) => $o['estado'] === $filtro_estado);
    }

    require_once __DIR__ . '/../vista/v_ordenesTrabajo.php';
?>