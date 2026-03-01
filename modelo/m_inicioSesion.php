<?php
// modelo/m_inicioSesion.php

function buscarUsuarioPorEmail($email, $bd) {
    // 1. Usamos "?" como marcador de posición (es lo que entiende MySQLi)
    $sql = "SELECT * FROM usuarios WHERE email = ? LIMIT 1";
    
    // 2. Preparamos la consulta
    $stmt = $bd->prepare($sql);
    
    // 3. "Enlazamos" el email al signo de interrogación
    // la "s" significa que el dato es un "string" (texto)
    $stmt->bind_param("s", $email);
    
    // 4. Ejecutamos
    $stmt->execute();
    
    // 5. Obtenemos el resultado
    $resultado = $stmt->get_result();
    
    // 6. Devolvemos los datos como un array (o false si no hay nada)
    return $resultado->fetch_assoc();
}
?>