<?php
require_once __DIR__ . '/m_conecta.php';

/**
 * Obtiene todos los vehículos de un cliente específico
 * @param int $cliente_id ID del cliente (usuario)
 * @param int $taller_id ID del taller
 * @param mysqli $conectar Conexión a la BD
 * @return array Array de vehículos o array vacío
 */
function obtenerVehiculosCliente($cliente_id, $taller_id, $conectar) {
    $query = "SELECT id, matricula, marca, modelo, anio, ultimo_kilometraje 
              FROM vehiculos 
              WHERE cliente_id = ? AND taller_id = ? 
              ORDER BY marca, modelo";
    
    $stmt = $conectar->prepare($query);
    $stmt->bind_param("ii", $cliente_id, $taller_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $vehiculos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $vehiculos[] = $fila;
    }
    
    $stmt->close();
    return $vehiculos;
}

/**
 * Obtiene un vehículo específico por su ID
 * @param int $vehiculo_id ID del vehículo
 * @param int $cliente_id ID del cliente (para validación)
 * @param mysqli $conectar Conexión a la BD
 * @return array|null Datos del vehículo o null
 */
function obtenerVehiculo($vehiculo_id, $cliente_id, $conectar) {
    $query = "SELECT id, matricula, marca, modelo, anio, ultimo_kilometraje 
              FROM vehiculos 
              WHERE id = ? AND cliente_id = ?";
    
    $stmt = $conectar->prepare($query);
    $stmt->bind_param("ii", $vehiculo_id, $cliente_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $vehiculo = $resultado->fetch_assoc();
    $stmt->close();
    
    return $vehiculo;
}

?>