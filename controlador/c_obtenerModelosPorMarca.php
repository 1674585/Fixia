<?php
// controlador/c_obtenerModelosPorMarca.php - Devuelve modelos según marca (JSON)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar permisos (igual que en c_buscarClientes.php)
$rolesPermitidos = ['jefe', 'recepcionista', 'mecanico'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/../modelo/m_registrarVehiculo.php';
require_once __DIR__ . '/../modelo/m_conecta.php';

$conectar = conectaBD();

$marcaId = intval($_GET['marcaId'] ?? 0);
if ($marcaId <= 0) {
    echo json_encode([]);
    exit;
}

$modelos = obtenerModelosPorMarca($conectar, $marcaId);

header('Content-Type: application/json');
echo json_encode($modelos);
?>