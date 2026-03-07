<div class="contenedor-detalles-vehiculo">
    
    <!-- INFORMACIÓN DEL VEHÍCULO -->
    <div class="card-vehiculo">
        <h1>Detalles del Vehículo</h1>
        
        <div class="info-vehiculo">
            <div class="fila-info">
                <label>Matrícula:</label>
                <span><?php echo htmlspecialchars($vehiculo['matricula']); ?></span>
            </div>
            <div class="fila-info">
                <label>Marca:</label>
                <span><?php echo htmlspecialchars($vehiculo['marca']); ?></span>
            </div>
            <div class="fila-info">
                <label>Modelo:</label>
                <span><?php echo htmlspecialchars($vehiculo['modelo']); ?></span>
            </div>
            <div class="fila-info">
                <label>Año:</label>
                <span><?php echo $vehiculo['anio'] ?: 'N/A'; ?></span>
            </div>
            <div class="fila-info">
                <label>Último Kilometraje:</label>
                <span><?php echo number_format($vehiculo['ultimo_kilometraje'], 0, '.', '.'); ?> km</span>
            </div>
        </div>

        <div class="botones-accion">
            <a href="index.php?action=misVehiculos" class="boton boton-secundario">
                ← Volver a Mis Vehículos
            </a>
        </div>
    </div>

    <!-- ÓRDENES DE TRABAJO -->
    <div class="card-ordenes">
        <h2>Histórico de Órdenes de Trabajo</h2>
        
        <?php if (empty($ordenes)): ?>
            <div class="alerta-info">
                <p>No hay órdenes de trabajo registradas para este vehículo.</p>
            </div>
        <?php else: ?>
            <div class="lista-ordenes">
                <?php foreach ($ordenes as $orden): ?>
                    <div class="card-orden">
                        <div class="encabezado-orden">
                            <div class="info-orden-principal">
                                <h3>Orden #<?php echo $orden['id']; ?></h3>
                                <span class="estado estado-<?php echo strtolower($orden['estado']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $orden['estado'])); ?>
                                </span>
                            </div>
                            <div class="fecha-orden">
                                <?php echo date('d/m/Y H:i', strtotime($orden['fecha_creacion'])); ?>
                            </div>
                        </div>

                        <div class="contenido-orden">
                            <div class="fila-orden">
                                <label>Creada por:</label>
                                <span><?php echo htmlspecialchars($orden['creado_por']); ?></span>
                            </div>

                            <?php if ($orden['sintomas_cliente']): ?>
                                <div class="fila-orden">
                                    <label>Síntomas reportados:</label>
                                    <span><?php echo htmlspecialchars($orden['sintomas_cliente']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($orden['diagnostico_tecnico']): ?>
                                <div class="fila-orden">
                                    <label>Diagnóstico técnico:</label>
                                    <span><?php echo htmlspecialchars($orden['diagnostico_tecnico']); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="fila-orden">
                                <label>Precio estimado:</label>
                                <span><?php echo $orden['precio_estimado_ia'] ? number_format($orden['precio_estimado_ia'], 2, ',', '.') . '€' : 'N/A'; ?></span>
                            </div>

                            <div class="fila-orden">
                                <label>Tiempo estimado:</label>
                                <span><?php echo $orden['tiempo_estimado_ia'] ? $orden['tiempo_estimado_ia'] . ' minutos' : 'N/A'; ?></span>
                            </div>
                        </div>

                        <!-- TAREAS DE LA ORDEN -->
                        <?php if (!empty($tareasOrdenes[$orden['id']])): ?>
                            <div class="subtareas">
                                <h4>Tareas realizadas:</h4>
                                <table class="tabla-tareas">
                                    <thead>
                                        <tr>
                                            <th>Tarea</th>
                                            <th>Mecánico</th>
                                            <th>Inicio</th>
                                            <th>Fin</th>
                                            <th>Duración</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tareasOrdenes[$orden['id']] as $tarea): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($tarea['nombre_tarea']); ?></td>
                                                <td><?php echo htmlspecialchars($tarea['mecanico']); ?></td>
                                                <td><?php echo $tarea['hora_inicio'] ? date('d/m/Y H:i', strtotime($tarea['hora_inicio'])) : 'Pendiente'; ?></td>
                                                <td><?php echo $tarea['hora_fin'] ? date('d/m/Y H:i', strtotime($tarea['hora_fin'])) : 'Pendiente'; ?></td>
                                                <td><?php echo $tarea['duracion_real_minutos'] ? $tarea['duracion_real_minutos'] . ' min' : '-'; ?></td>
                                                <td><span class="estado-tarea estado-<?php echo strtolower($tarea['estado']); ?>"><?php echo ucfirst($tarea['estado']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
