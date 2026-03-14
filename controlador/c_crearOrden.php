<?php
// session_start(); // QUITA ESTA LÍNEA: la sesión ya está activa desde index.php

require_once __DIR__ . '/../modelo/m_crearOrden.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?action=inicioSesion");
    exit;
}

$taller_id = $_SESSION['taller_id'];
$usuario_id = $_SESSION['user_id']; // CAMBIA: usa 'user_id' en lugar de 'usuario_id'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar creación
    $vehiculo_id = $_POST['vehiculo_id'];
    $sintomas_cliente = $_POST['sintomas_cliente'];
    $subgrupo_id = $_POST['subgrupo_reparacion'] ?? null;

    try {
        $orden_id = crearOrdenTrabajo($taller_id, $vehiculo_id, $usuario_id, $sintomas_cliente, $subgrupo_id);
        // Redirigir a detalles de la orden o home
        header("Location: index.php?action=detallesOrden&id=$orden_id");
        exit;
    } catch (Exception $e) {
        echo "Error al crear orden: " . $e->getMessage();
    }
} else {
    // Cargar datos para la vista
    $tipos = obtenerTiposReparacion();
    $vehiculos = obtenerVehiculosPorTaller($taller_id);

    // Incluir la vista
    require_once __DIR__ . '/../vista/v_crearOrden.php';
}
?>