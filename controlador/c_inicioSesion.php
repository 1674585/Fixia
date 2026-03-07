<?php
// controlador/c_inicioSesion.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../modelo/m_inicioSesion.php';
require_once __DIR__ . '/../modelo/m_conecta.php';

$conectar = conectaBD();
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $emailInput = $_POST['mail'] ?? '';
    $passInput  = $_POST['password'] ?? '';

    $usuario = buscarUsuarioPorEmail($emailInput, $conectar);

    if ($usuario) {
        if (password_verify($passInput, $usuario['password_hash'])) {
            
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre_completo'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['taller_id'] = $usuario['taller_id'];

            // Redireccionar según el rol
            switch ($_SESSION['rol']) {
                case 'cliente':
                    header("Location: /Fixia/index.php?action=misVehiculos");
                    exit;
                case 'jefe':
                    header("Location: /Fixia/index.php?action=ordenesTrabajoJefe");
                    exit;
                case 'recepcionista':
                    header("Location: /Fixia/index.php?action=registrarVehiculo");
                    exit;
                case 'mecanico':
                    header("Location: /Fixia/index.php?action=misTareas");
                    exit;
                case 'ceo':
                    header("Location: /Fixia/index.php?action=dashboard");
                    exit;
                default:
                    header("Location: /Fixia/index.php?action=home");
                    exit;
            }

            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "El usuario no existe en este taller.";
    }
}

$mensajeError = $error;

require __DIR__ . '/../vista/v_inicioSesion.php';
?>