<?php
require_once __DIR__ . '/../modelo/m_detallesOrden.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?action=inicioSesion");
    exit;
}

$taller_id = $_SESSION['taller_id'];
$orden_id = $_GET['id'] ?? null;

if (!$orden_id || !is_numeric($orden_id)) {
    echo "ID de orden inválido.";
    exit;
}

// Obtener datos
$orden = obtenerOrdenPorId($orden_id, $taller_id);
if (!$orden) {
    echo "Orden no encontrada.";
    exit;
}

$vehiculo = obtenerVehiculoPorId($orden['vehiculo_id'], $taller_id);
$tareas = obtenerTareasAsignadas($orden_id);

// Incluir la vista
require_once __DIR__ . '/../vista/v_detallesOrden.php';
?>