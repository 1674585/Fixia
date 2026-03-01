<?php
// controlador/c_registroUsuario.php
require_once __DIR__ . '/../modelo/m_registroUsuario.php';
require_once __DIR__ . '/../modelo/m_conecta.php';

$conectar = conectaBD();
$mensaje = "";
$tipoMensaje = ""; // para saber si es error o éxito

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre   = $_POST['nombre_completo'] ?? '';
    $email    = $_POST['mail'] ?? '';
    $pass     = $_POST['password'] ?? '';
    $tel      = $_POST['telefono'] ?? '';
    $taller   = $_POST['taller_id'] ?? '';

    // Validación básica
    if (!empty($nombre) && !empty($email) && !empty($pass) && !empty($taller)) {
        
        // Comprobar si el email ya existe en ese taller
        if (emailExiste($conectar, $email, $taller)) {
            $mensaje = "Este correo ya está registrado en este taller.";
            $tipoMensaje = "error";
        } else {
            // Intentar registrar
            if (registrarNuevoUsuario($conectar, $taller, $nombre, $email, $pass, $tel)) {
                $mensaje = "¡Registro completado con éxito! Ya puedes iniciar sesión.";
                $tipoMensaje = "exito";
            } else {
                $mensaje = "Hubo un error al guardar los datos.";
                $tipoMensaje = "error";
            }
        }
    } else {
        $mensaje = "Por favor, rellena todos los campos obligatorios.";
        $tipoMensaje = "error";
    }
}

require __DIR__ . '/../vista/v_registroUsuario.php';