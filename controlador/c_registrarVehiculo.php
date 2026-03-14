<?php
// controlador/c_registrarVehiculo.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario tenga permisos (jefe, recepcionista o mecánico)
$rolesPermitidos = ['jefe', 'recepcionista', 'mecanico'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
    header("Location: index.php?action=inicioSesion");
    exit;
}

require_once __DIR__ . '/../modelo/m_registrarVehiculo.php';
require_once __DIR__ . '/../modelo/m_conecta.php';

$conectar = conectaBD();



$taller_id = $_SESSION['taller_id'];
$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = intval($_POST['cliente_id'] ?? 0);
    
    if ($cliente_id <= 0) {
        $mensaje = 'Debes seleccionar un cliente válido.';
        $tipo_mensaje = 'error';
    } else {
        $datos = [
            'taller_id' => $taller_id,
            'cliente_id' => $cliente_id,
            'matricula' => strtoupper(trim($_POST['matricula'] ?? '')),
            'marca' => trim($_POST['marca'] ?? ''),
            'modelo' => trim($_POST['modelo'] ?? ''),
            'anio' => intval($_POST['anio'] ?? 0),
            'kilometraje' => intval($_POST['kilometraje'] ?? 0)
        ];
        
        // Validar campos obligatorios
        if (empty($datos['matricula']) || empty($datos['marca']) || empty($datos['modelo'])) {
            $mensaje = 'Por favor completa todos los campos obligatorios.';
            $tipo_mensaje = 'error';
        } else {
            // Registrar el vehículo
            $resultado = registrarVehiculo($datos, $conectar);
            $mensaje = $resultado['mensaje'];
            $tipo_mensaje = $resultado['success'] ? 'exito' : 'error';
            
            // Si se registró exitosamente, limpiar el formulario
            if ($resultado['success']) {
                $datos = [
                    'cliente_id' => 0,
                    'matricula' => '',
                    'marca' => '',
                    'modelo' => '',
                    'anio' => date('Y'),
                    'kilometraje' => 0
                ];
            }
        }
    }
}
if (!isset($datos)) {
    $datos = [
        'cliente_id' => 0,
        'matricula' => '',
        'marca' => '',
        'modelo' => '',
        'anio' => date('Y'),
        'kilometraje' => 0
    ];
}

$marcas = obtenerMarcas($conectar);


require __DIR__ . '/../vista/v_registrarVehiculo.php';

$conectar->close();
?>