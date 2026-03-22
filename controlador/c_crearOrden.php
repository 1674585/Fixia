<?php
require_once __DIR__ . '/../modelo/m_crearOrden.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?action=inicioSesion");
    exit;
}

$taller_id = $_SESSION['taller_id'];
$usuario_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $vehiculo_id = $_POST['vehiculo_id'];
    $sintomas_cliente = $_POST['sintomas_cliente'];
    $tareas = $_POST['tareas'] ?? [];
    $estado = $_POST['estado'] ?? 'recibido';

    try {
        $orden_id = crearOrdenTrabajo(
            $taller_id,
            $vehiculo_id,
            $usuario_id,
            $sintomas_cliente,
            $tareas,
            $estado
        );

        header("Location: index.php?action=detallesOrden&id=$orden_id");
        exit;

    } catch (Exception $e) {
        echo "Error al crear orden: " . $e->getMessage();
    }

} else {

    // Cargar datos
    $tipos = obtenerTiposReparacion();
    $vehiculos = obtenerVehiculosPorTaller($taller_id);

    require_once __DIR__ . '/../vista/v_crearOrden.php';
}
?>