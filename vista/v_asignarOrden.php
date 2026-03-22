<?php
// ─────────────────────────────────────────────
// Vista: Asignar orden de trabajo a mecánico
// Espera: $orden (array), $mecanicos (array),
//         $error (string|null), $orden_id (int)
// ─────────────────────────────────────────────
?>
 
<div class="asignar-container">
 
    <!-- Cabecera -->
    <div class="asignar-header">
        <a href="index.php?action=ordenesTrabajo" class="btn-volver">← Volver a órdenes</a>
        <h2>Asignar Orden #<?= htmlspecialchars($orden['id']) ?></h2>
    </div>
 
    <?php if ($error): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
 
    <div class="asignar-grid">
 
        <!-- Tarjeta: Resumen de la orden -->
        <div class="card resumen-orden">
            <h3 class="card-titulo">Resumen de la orden</h3>
 
            <div class="dato-fila">
                <span class="dato-label">Vehículo</span>
                <span><?= htmlspecialchars($orden['matricula'] . ' — ' . $orden['marca'] . ' ' . $orden['modelo'] . ' (' . $orden['anio'] . ')') ?></span>
            </div>
 
            <div class="dato-fila">
                <span class="dato-label">Cliente</span>
                <span><?= htmlspecialchars($orden['nombre_cliente']) ?>
                    <?php if ($orden['telefono_cliente']): ?>
                        <small class="telefono"><?= htmlspecialchars($orden['telefono_cliente']) ?></small>
                    <?php endif; ?>
                </span>
            </div>
 
            <div class="dato-fila">
                <span class="dato-label">Estado actual</span>
                <span class="badge badge-<?= str_replace('_', '-', $orden['estado']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $orden['estado'])) ?>
                </span>
            </div>
 
            <div class="dato-fila">
                <span class="dato-label">Creada el</span>
                <span><?= date('d/m/Y H:i', strtotime($orden['fecha_creacion'])) ?></span>
            </div>
 
            <?php if ($orden['sintomas_cliente']): ?>
                <div class="dato-bloque">
                    <span class="dato-label">Síntomas reportados</span>
                    <p class="dato-texto"><?= nl2br(htmlspecialchars($orden['sintomas_cliente'])) ?></p>
                </div>
            <?php endif; ?>
 
            <?php if ($orden['diagnostico_tecnico']): ?>
                <div class="dato-bloque">
                    <span class="dato-label">Diagnóstico técnico</span>
                    <p class="dato-texto"><?= nl2br(htmlspecialchars($orden['diagnostico_tecnico'])) ?></p>
                </div>
            <?php endif; ?>
 
            <?php if ($orden['nombre_mecanico']): ?>
                <div class="dato-fila aviso-reasignacion">
                    <span class="dato-label">⚠ Actualmente asignada a</span>
                    <strong><?= htmlspecialchars($orden['nombre_mecanico']) ?></strong>
                </div>
            <?php endif; ?>
        </div>
 
        <!-- Tarjeta: Formulario de asignación -->
        <div class="card formulario-asignacion">
            <h3 class="card-titulo">Seleccionar mecánico</h3>
 
            <?php if (empty($mecanicos)): ?>
                <div class="alerta alerta-warning">
                    No hay mecánicos registrados en el taller. 
                    <a href="index.php?action=registroUsuario">Añadir mecánico</a>
                </div>
            <?php else: ?>
                <form method="POST" action="index.php?action=asignarOrden&id=<?= $orden_id ?>">
 
                    <div class="lista-mecanicos">
                        <?php foreach ($mecanicos as $mec): ?>
                            <label class="mecanico-opcion <?= ($orden['asignado_a_id'] == $mec['id']) ? 'seleccionado-actual' : '' ?>">
                                <input type="radio"
                                       name="mecanico_id"
                                       value="<?= $mec['id'] ?>"
                                       <?= ($orden['asignado_a_id'] == $mec['id']) ? 'checked' : '' ?>>
                                <div class="mecanico-info">
                                    <span class="mecanico-nombre"><?= htmlspecialchars($mec['nombre_completo']) ?></span>
                                    <?php if ($mec['telefono']): ?>
                                        <span class="mecanico-tel"><?= htmlspecialchars($mec['telefono']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($orden['asignado_a_id'] == $mec['id']): ?>
                                    <span class="badge-actual">Asignado</span>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
 
                    <p class="nota-asignacion">
                        Al asignar la orden, <strong>todas las tareas pendientes</strong> quedarán asignadas al mecánico seleccionado y el estado pasará a <em>En reparación</em>.
                    </p>
 
                    <div class="acciones-form">
                        <a href="index.php?action=ordenesTrabajo" class="btn btn-cancelar">Cancelar</a>
                        <button type="submit" class="btn btn-confirmar">Confirmar asignación</button>
                    </div>
 
                </form>
            <?php endif; ?>
        </div>
 
    </div>
</div>