<?php
// controlador/c_obtenerSubgrupos.php

// Incluir el modelo donde está la función
require_once __DIR__ . '/../modelo/m_crearOrden.php';

// Verificar que se pase tipo_id y sea un número válido
if (!isset($_GET['tipo_id']) || !is_numeric($_GET['tipo_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'tipo_id inválido']);
    exit;
}

$tipo_id = (int) $_GET['tipo_id'];

try {
    // Llamar a la función existente
    $subgrupos = obtenerSubgruposReparacion($tipo_id);

    // Devolver como JSON
    header('Content-Type: application/json');
    echo json_encode($subgrupos);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Error al obtener subgrupos: ' . $e->getMessage()]);
}
?>