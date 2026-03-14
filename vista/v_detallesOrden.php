<?php
// Variables disponibles: $orden, $vehiculo, $tareas
?>
<h2>Detalles de la Orden de Trabajo #<?php echo $orden['id']; ?></h2>

<h3>Información de la Orden</h3>
<p><strong>Estado:</strong> <?php echo ucfirst($orden['estado']); ?></p>
<p><strong>Síntomas del Cliente:</strong> <?php echo htmlspecialchars($orden['sintomas_cliente']); ?></p>
<p><strong>Diagnóstico Técnico:</strong> <?php echo htmlspecialchars($orden['diagnostico_tecnico'] ?? 'No disponible'); ?></p>
<p><strong>Fecha de Creación:</strong> <?php echo $orden['fecha_creacion']; ?></p>

<h3>Información del Vehículo</h3>
<p><strong>Matrícula:</strong> <?php echo htmlspecialchars($vehiculo['matricula']); ?></p>
<p><strong>Marca y Modelo:</strong> <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?></p>
<p><strong>Año:</strong> <?php echo $vehiculo['anio']; ?></p>
<p><strong>Kilometraje:</strong> <?php echo $vehiculo['ultimo_kilometraje']; ?> km</p>

<h3>Tareas Asignadas</h3>
<?php if (empty($tareas)): ?>
    <p>No hay tareas asignadas aún.</p>
<?php else: ?>
    <ul>
        <?php foreach ($tareas as $tarea): ?>
            <li>
                <strong><?php echo htmlspecialchars($tarea['nombre_tarea']); ?></strong> - Estado: <?php echo ucfirst($tarea['estado']); ?>
                <?php if ($tarea['mecanico_nombre']): ?>
                    (Asignado a: <?php echo htmlspecialchars($tarea['mecanico_nombre']); ?>)
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<a href="index.php?action=home">Volver al Inicio</a>