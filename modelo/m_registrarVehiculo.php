<?php
require_once __DIR__ . '/m_conecta.php';

/**
 * Registra un nuevo vehículo
 * @param array $datos Array con los datos del vehículo
 * @param mysqli $conectar Conexión a la BD
 * @return array Array con 'success' y 'mensaje'
 */
function registrarVehiculo($datos, $conectar) {
    // Validar matrícula única por taller
    $queryMatricula = "SELECT id FROM vehiculos WHERE matricula = ? AND taller_id = ?";
    $stmtMatricula = $conectar->prepare($queryMatricula);
    $stmtMatricula->bind_param("si", $datos['matricula'], $datos['taller_id']);
    $stmtMatricula->execute();
    $resultadoMatricula = $stmtMatricula->get_result();
    
    if ($resultadoMatricula->num_rows > 0) {
        $stmtMatricula->close();
        return [
            'success' => false,
            'mensaje' => 'El vehículo con esta matrícula ya está registrado en este taller.'
        ];
    }
    $stmtMatricula->close();
    
    // Insertar el vehículo
    $query = "INSERT INTO vehiculos (taller_id, cliente_id, matricula, marca, modelo, anio, ultimo_kilometraje) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conectar->prepare($query);
    $stmt->bind_param("iisssii", 
        $datos['taller_id'],
        $datos['cliente_id'],
        $datos['matricula'],
        $datos['marca'],
        $datos['modelo'],
        $datos['anio'],
        $datos['kilometraje']
    );
    
    if ($stmt->execute()) {
        $stmt->close();
        return [
            'success' => true,
            'mensaje' => 'Vehículo registrado correctamente.',
            'vehiculo_id' => $conectar->insert_id
        ];
    } else {
        $stmt->close();
        return [
            'success' => false,
            'mensaje' => 'Error al registrar el vehículo. Intenta de nuevo.'
        ];
    }
}

function obtenerMarcas($conectar) {
    $query = "SELECT Id AS marca_id, Name AS nombre FROM CarMakes ORDER BY Name";
    $result = $conectar->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerModelosPorMarca($conectar, $marcaId) {
    $query = "SELECT Id AS modelo_id, Name AS nombre FROM CarModels WHERE MakeId = ? ORDER BY Name";
    $stmt = $conectar->prepare($query);
    $stmt->bind_param("i", $marcaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $modelos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $modelos;
}

?>