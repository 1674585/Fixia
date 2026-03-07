<?php

// controlador/c_detallesVehiculo.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario sea cliente y esté autenticado
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: index.php?action=inicioSesion");
    exit;
}

require_once __DIR__ . '/../modelo/m_detallesVehiculo.php';
require_once __DIR__ . '/../modelo/m_conecta.php';

$conectar = conectaBD();

$cliente_id = $_SESSION['user_id'];
$vehiculo_id = $_GET['id'] ?? null;

// Validar que se haya pasado un ID válido
if (!$vehiculo_id || !is_numeric($vehiculo_id)) {
    header("Location: index.php?action=misVehiculos");
    exit;
}

// Obtener datos del vehículo
$vehiculo = obtenerVehiculo($vehiculo_id, $cliente_id, $conectar);

// Si el vehículo no existe o no pertenece al cliente, redirigir
if (!$vehiculo) {
    header("Location: index.php?action=misVehiculos");
    exit;
}

// Obtener órdenes de trabajo del vehículo
$ordenes = obtenerOrdenesVehiculo($vehiculo_id, $cliente_id, $conectar);

// Si hay órdenes, obtener las tareas de cada una
$tareasOrdenes = [];
if (!empty($ordenes)) {
    foreach ($ordenes as $orden) {
        $tareasOrdenes[$orden['id']] = obtenerTareasOrden($orden['id'], $conectar);
    }
}

require __DIR__ . '/../vista/v_detallesVehiculo.php';

$conectar->close();
?>