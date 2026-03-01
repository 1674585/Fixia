<?php
// modelo/m_registroUsuario.php

function registrarNuevoUsuario($bd, $taller_id, $nombre, $email, $password_plana, $telefono) {
    // 1. Hasheamos la contraseña antes de guardarla
    $password_segura = password_hash($password_plana, PASSWORD_DEFAULT);
    
    // 2. Definimos el rol por defecto
    $rol = 'cliente';

    // 3. Preparamos la consulta SQL
    $sql = "INSERT INTO usuarios (taller_id, rol, nombre_completo, email, password_hash, telefono) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $bd->prepare($sql);
    
    // "isssss" significa: integer, string, string, string, string, string
    $stmt->bind_param("isssss", $taller_id, $rol, $nombre, $email, $password_segura, $telefono);
    
    return $stmt->execute();
}

function emailExiste($bd, $email, $taller_id) {
    $sql = "SELECT id FROM usuarios WHERE email = ? AND taller_id = ?";
    $stmt = $bd->prepare($sql);
    $stmt->bind_param("si", $email, $taller_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado->num_rows > 0;
}