<?php
    // ─────────────────────────────────────────────
    // Búsqueda AJAX de vehículos por matrícula, marca,
    // modelo o nombre del cliente propietario.
    // Multi-tenant: solo vehículos del taller en sesión.
    // ─────────────────────────────────────────────

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');

    $roles_permitidos = ['ceo', 'jefe', 'recepcionista', 'mecanico'];
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'] ?? '', $roles_permitidos)) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }

    require_once __DIR__ . '/../modelo/m_conecta.php';

    $taller_id = (int)$_SESSION['taller_id'];
    $query     = trim($_GET['q'] ?? '');

    if (strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }

    $conectar = conectaBD();

    $like = '%' . $query . '%';
    $sql  = "SELECT v.id,
                    v.matricula,
                    v.marca,
                    v.modelo,
                    v.anio,
                    cliente.nombre_completo AS nombre_cliente
             FROM vehiculos v
             INNER JOIN usuarios cliente ON v.cliente_id = cliente.id
             WHERE v.taller_id = ?
               AND (v.matricula LIKE ?
                    OR v.marca LIKE ?
                    OR v.modelo LIKE ?
                    OR cliente.nombre_completo LIKE ?)
             ORDER BY v.matricula ASC
             LIMIT 10";

    $stmt = $conectar->prepare($sql);
    $stmt->bind_param("issss", $taller_id, $like, $like, $like, $like);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $vehiculos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $vehiculos[] = $fila;
    }

    $stmt->close();
    $conectar->close();

    echo json_encode($vehiculos);
?>
