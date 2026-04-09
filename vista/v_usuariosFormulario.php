<?php
// ─────────────────────────────────────────────
// Vista: Formulario crear / editar usuario
// Espera: $usuario, $es_edicion, $error,
//         $roles_disponibles
// ─────────────────────────────────────────────

$roles_labels = [
    'jefe'          => ['label' => 'Jefe',         'desc' => 'Puede asignar órdenes, ver reportes y gestionar usuarios'],
    'recepcionista' => ['label' => 'Recepcionista', 'desc' => 'Crea órdenes, gestiona facturación y stock'],
    'mecanico'      => ['label' => 'Mecánico',      'desc' => 'Ejecuta tareas asignadas y registra tiempos'],
    'cliente'       => ['label' => 'Cliente',       'desc' => 'Puede ver sus vehículos y aprobar presupuestos'],
];
?>

<div class="uf-container">

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php?action=usuarios">Usuarios</a>
        <span class="bc-sep">›</span>
        <span><?= $es_edicion ? 'Editar usuario' : 'Nuevo usuario' ?></span>
    </nav>

    <div class="uf-header">
        <h2><?= $es_edicion ? '✏ Editar usuario' : '+ Nuevo usuario' ?></h2>
    </div>

    <?php if ($error): ?>
        <div class="alerta alerta-error">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST"
          action="index.php?action=usuariosFormulario<?= $es_edicion ? '&id=' . (int)$usuario['id'] : '' ?>"
          class="uf-form"
          novalidate>
        <input type="hidden" name="usuario_id" value="<?= (int)($usuario['id'] ?? 0) ?>">

        <div class="uf-grid">

            <!-- Sección: Datos personales -->
            <div class="uf-seccion">
                <h3 class="uf-seccion-titulo">Datos personales</h3>

                <div class="campo">
                    <label class="campo-label" for="nombre_completo">
                        Nombre completo <span class="obligatorio">*</span>
                    </label>
                    <input type="text"
                           id="nombre_completo"
                           name="nombre_completo"
                           class="campo-input"
                           placeholder="Nombre y apellidos"
                           value="<?= htmlspecialchars($usuario['nombre_completo'] ?? '') ?>"
                           required maxlength="100">
                </div>

                <div class="campo">
                    <label class="campo-label" for="email">
                        Email <span class="obligatorio">*</span>
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           class="campo-input"
                           placeholder="correo@ejemplo.com"
                           value="<?= htmlspecialchars($usuario['email'] ?? '') ?>"
                           required maxlength="100">
                </div>

                <div class="campo">
                    <label class="campo-label" for="telefono">Teléfono</label>
                    <input type="tel"
                           id="telefono"
                           name="telefono"
                           class="campo-input"
                           placeholder="+34 600 000 000"
                           value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>"
                           maxlength="20">
                </div>
            </div>

            <!-- Sección: Rol -->
            <div class="uf-seccion">
                <h3 class="uf-seccion-titulo">Rol en el taller</h3>

                <div class="roles-opciones">
                    <?php foreach ($roles_disponibles as $rol):
                        $rl = $roles_labels[$rol];
                    ?>
                        <label class="rol-opcion <?= ($usuario['rol'] ?? '') === $rol ? 'seleccionado' : '' ?>">
                            <input type="radio"
                                   name="rol"
                                   value="<?= $rol ?>"
                                   <?= ($usuario['rol'] ?? '') === $rol ? 'checked' : '' ?>>
                            <div class="rol-opcion-info">
                                <span class="rol-opcion-nombre"><?= $rl['label'] ?></span>
                                <span class="rol-opcion-desc"><?= $rl['desc'] ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sección: Contraseña (solo en creación) -->
            <?php if (!$es_edicion): ?>
                <div class="uf-seccion">
                    <h3 class="uf-seccion-titulo">Contraseña inicial</h3>
                    <div class="campo">
                        <label class="campo-label" for="password">
                            Contraseña <span class="obligatorio">*</span>
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="campo-input"
                               placeholder="Mínimo 8 caracteres"
                               minlength="8"
                               required>
                        <span class="campo-ayuda">
                            El usuario podrá cambiarla desde su perfil. Para cambiar la contraseña de un usuario existente, usa el botón "🔑 Contraseña" en el listado.
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div class="uf-seccion uf-seccion-info">
                    <p class="uf-info-pass">🔑 Para cambiar la contraseña de este usuario, usa el botón <strong>"Contraseña"</strong> en el listado de usuarios.</p>
                </div>
            <?php endif; ?>

        </div>

        <div class="uf-acciones">
            <a href="index.php?action=usuarios" class="btn btn-cancelar">Cancelar</a>
            <button type="submit" class="btn btn-guardar">
                <?= $es_edicion ? '💾 Guardar cambios' : '+ Crear usuario' ?>
            </button>
        </div>
    </form>
</div>

<script>
    // Resaltar opción de rol seleccionada
    document.querySelectorAll('.rol-opcion input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('.rol-opcion').forEach(el => el.classList.remove('seleccionado'));
            radio.closest('.rol-opcion').classList.add('seleccionado');
        });
    });
</script>

<style>
    .uf-container { padding: 1.5rem; max-width: 760px; margin: 0 auto; }

    .breadcrumb   { display: flex; align-items: center; gap: .4rem; font-size: .85rem; margin-bottom: 1.2rem; }
    .breadcrumb a { color: #2563eb; text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .bc-sep       { color: #d1d5db; }

    .uf-header h2 { margin: 0 0 1.3rem; font-size: 1.35rem; color: #111827; }

    .uf-grid { display: flex; flex-direction: column; gap: 1.2rem; margin-bottom: 1.5rem; }

    .uf-seccion {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 10px; padding: 1.2rem 1.3rem;
    }
    .uf-seccion-titulo {
        font-size: .85rem; font-weight: 700; color: #6b7280;
        text-transform: uppercase; letter-spacing: .06em;
        margin: 0 0 1rem; padding-bottom: .5rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .uf-seccion-info { background: #f0f9ff; border-color: #bae6fd; }
    .uf-info-pass    { margin: 0; font-size: .88rem; color: #0369a1; }

    /* Campos */
    .campo        { margin-bottom: .9rem; }
    .campo:last-child { margin-bottom: 0; }
    .campo-label  { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
    .obligatorio  { color: #dc2626; }
    .campo-input  {
        width: 100%; padding: .6rem .85rem;
        border: 1.5px solid #d1d5db; border-radius: 7px;
        font-size: .92rem; color: #111827; background: #fafafa;
        transition: border-color .15s; box-sizing: border-box;
    }
    .campo-input:focus { outline: none; border-color: #2563eb; background: #fff; }
    .campo-ayuda  { display: block; font-size: .76rem; color: #9ca3af; margin-top: .3rem; line-height: 1.4; }

    /* Roles */
    .roles-opciones { display: flex; flex-direction: column; gap: .55rem; }
    .rol-opcion {
        display: flex; align-items: flex-start; gap: .8rem;
        padding: .7rem .9rem; border: 2px solid #e5e7eb;
        border-radius: 8px; cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .rol-opcion:hover      { border-color: #93c5fd; background: #f8fbff; }
    .rol-opcion.seleccionado { border-color: #2563eb; background: #eff6ff; }
    .rol-opcion input      { display: none; }
    .rol-opcion-info       { display: flex; flex-direction: column; gap: .15rem; }
    .rol-opcion-nombre     { font-size: .9rem; font-weight: 600; color: #111827; }
    .rol-opcion-desc       { font-size: .78rem; color: #9ca3af; }

    /* Acciones */
    .uf-acciones  { display: flex; justify-content: flex-end; gap: .8rem; }
    .btn          { display: inline-block; padding: .6rem 1.3rem; border-radius: 7px; font-size: .92rem; cursor: pointer; border: none; text-decoration: none; font-weight: 500; }
    .btn-cancelar { background: #e5e7eb; color: #374151; }
    .btn-guardar  { background: #2563eb; color: #fff; }
    .btn-guardar:hover { background: #1d4ed8; }

    /* Alertas */
    .alerta       { padding: .8rem 1rem; border-radius: 7px; margin-bottom: 1rem; font-size: .9rem; }
    .alerta-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>