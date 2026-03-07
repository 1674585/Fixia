<div class="contenedor-vehiculos">
    <h1>Mis Vehículos</h1>
    
    <?php if (empty($vehiculos)): ?>
        <div class="alerta-info">
            <p>No tienes vehículos registrados en este taller.</p>
            <p>Contacta con la recepción para registrar un nuevo vehículo.</p>
        </div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table class="tabla-vehiculos">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Año</th>
                        <th>Último Kilometraje</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehiculos as $vehiculo): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($vehiculo['matricula']); ?></strong></td>
                            <td><?php echo htmlspecialchars($vehiculo['marca']); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['modelo']); ?></td>
                            <td><?php echo $vehiculo['anio'] ?: 'N/A'; ?></td>
                            <td><?php echo number_format($vehiculo['ultimo_kilometraje'], 0, '.', '.'); ?> km</td>
                            <td>
                                <a href="/Fixia/index.php?action=detallesVehiculo&id=<?php echo $vehiculo['id']; ?>" 
                                   class="boton boton-primario">
                                   Ver Detalles
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>