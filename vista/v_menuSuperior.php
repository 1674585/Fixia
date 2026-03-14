<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fixia</title>
  <script src="/js/fetch.js"></script>
</head>

<header>
  <div class="navbar">
    <a href="index.php?action=home" class="logo"><img src="/img/logo.png" width="120px"></a>

    <div class="menu">
      <?php
        // Asegurar que la sesión está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si hay sesión activa
        if (isset($_SESSION['user_id'])) {
            $rol = $_SESSION['rol'] ?? '';
            $nombre = $_SESSION['nombre'] ?? 'Usuario';
            
            echo "<span class='usuario-info'>Hola, " . htmlspecialchars($nombre) . " (" . htmlspecialchars($rol) . ")</span>";
            
            // Botones específicos según el rol
            switch ($rol) {
                case 'ceo':
                    echo '<a href="index.php?action=registrarTaller">Registrar Taller</a>';
                    echo '<a href="index.php?action=usuarios">Usuarios</a>';
                    break;
                
                case 'jefe':
                    echo '<a href="index.php?action=ordenesTrabajo">Órdenes de Trabajo</a>';
                    echo '<a href="index.php?action=reportes">Reportes</a>';
                    echo '<a href="index.php?action=misTareas">Mis Tareas</a>';
                    echo '<a href="index.php?action=usuarios">Gestionar Usuarios</a>';
                    echo '<a href="index.php?action=registrarVehiculo">Registrar Vehículo</a>';
                    echo '<a href="index.php?action=crearOrden">Crear Orden</a>';
                    break;
                
                case 'recepcionista':
                    echo '<a href="index.php?action=registrarVehiculo">Registrar Vehículo</a>';
                    echo '<a href="index.php?action=stock">Stock</a>';
                    echo '<a href="index.php?action=facturacion">Facturación</a>';
                    echo '<a href="index.php?action=crearOrden">Crear Orden</a>';
                    break;
                
                case 'mecanico':
                    echo '<a href="index.php?action=registrarVehiculo">Registrar Vehículo</a>';
                    echo '<a href="index.php?action=stock">Stock</a>';
                    echo '<a href="index.php?action=facturacion">Facturación</a>';
                    echo '<a href="index.php?action=crearOrden">Crear Orden</a>';
                    echo '<a href="index.php?action=misTareas">Mis Tareas</a>';
                    break;
                
                case 'cliente':
                    echo '<a href="index.php?action=misVehiculos">Mis Vehículos</a>';
                    echo '<a href="index.php?action=aprobarPresupuesto">Aprobar Presupuestos</a>';
                    break;
            }
            
            // Botón de cerrar sesión (disponible para todos los roles)
            echo '<a href="index.php?action=cerrarSesion">Cerrar Sesión</a>';
        } else {
            // Si NO hay sesión activa, mostrar botones de login y registro
            echo '<a href="index.php?action=inicioSesion">Iniciar Sesión</a>';
            echo '<a href="index.php?action=registroUsuario">Registrarse</a>';
        }
      ?>
    </div>
   
  </div>
</header>