<?php
// controlador/c_inicioSesion.php

// Solo arrancamos sesión si todavía no se ha iniciado (la abre index.php
// la mayoría de las veces).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si has renombrado el fichero a m_inicioSesion.php, deja esta línea.
// Si sigues con el nombre antiguo pon m__inicioSesion.php.
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
            
            $_SESSION['user_id']   = $usuario['id'];
            $_SESSION['nombre']    = $usuario['nombre_completo'];
            $_SESSION['rol']       = $usuario['rol'];
            $_SESSION['taller_id'] = $usuario['taller_id'];

            header("Location: /index.php?action=home");

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