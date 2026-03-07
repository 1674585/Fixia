<?php
// controlador/c_misVehiculos.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario sea cliente y esté autenticado
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: /index.php?action=inicioSesion");
    exit;
}

require_once __DIR__ . '/../modelo/m_misVehiculos.php';
require_once __DIR__ . '/../modelo/m_conecta.php';

$conectar = conectaBD();

$cliente_id = $_SESSION['user_id'];
$taller_id = $_SESSION['taller_id'];

// Obtener todos los vehículos del cliente
$vehiculos = obtenerVehiculosCliente($cliente_id, $taller_id, $conectar);

require __DIR__ . '/../vista/v_misVehiculos.php';

$conectar->close();
?>