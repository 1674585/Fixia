<?php
    // ─────────────────────────────────────────────────────────────────
    // Endpoint AJAX: Buscar productos del taller para el buscador live
    // Devuelve JSON con array de productos
    // Llamada: index.php?action=buscarProductosTarea&q=TEXTO
    // ─────────────────────────────────────────────────────────────────

    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        echo json_encode(['error' => 'Sin sesión']);
        exit;
    }

    require_once __DIR__ . '/../modelo/m_misTareas.php';

    $taller_id = (int)$_SESSION['taller_id'];
    $q         = trim($_GET['q'] ?? '');

    if (strlen($q) < 2) {
        echo json_encode([]);
        exit;
    }

    $productos = buscarProductosParaTarea($taller_id, $q);
    echo json_encode($productos);
    exit;
?>