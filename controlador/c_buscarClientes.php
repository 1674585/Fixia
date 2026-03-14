<?php
// controlador/c_buscarClientes.php - Para búsqueda AJAX

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario tenga permisos
$rolesPermitidos = ['jefe', 'recepcionista', 'mecanico'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/../modelo/m_conecta.php';

$conectar = conectaBD();
$taller_id = $_SESSION['taller_id'];

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$query = "%$query%";
$sql = "SELECT id, nombre_completo, email 
        FROM usuarios 
        WHERE taller_id = ? AND rol = 'cliente' 
        AND (email LIKE ? OR nombre_completo LIKE ?)
        ORDER BY nombre_completo
        LIMIT 10";

$stmt = $conectar->prepare($sql);
$stmt->bind_param("iss", $taller_id, $query, $query);
$stmt->execute();
$resultado = $stmt->get_result();

$clientes = [];
while ($fila = $resultado->fetch_assoc()) {
    $clientes[] = $fila;
}

$stmt->close();
$conectar->close();

header('Content-Type: application/json');
echo json_encode($clientes);
?>