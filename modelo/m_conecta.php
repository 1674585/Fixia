<?php

    function conectaBD() {

        $servername = "127.0.0.1";
        $username = "root";
        $password = "";
        $dbname = "fixia";

        $conexion = new mysqli($servername, $username, $password, $dbname);

        if ($conexion->connect_error) {
            die("Error de conexión: " . $conexion->connect_error);
        }
        
        return $conexion;
    }
?>