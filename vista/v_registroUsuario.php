<div class="registro-container">
    <h1>Crear Cuenta de Cliente</h1>

    <?php if ($mensaje !== ''): ?>
        <p style="color: <?= $tipoMensaje === 'exito' ? 'green' : 'red' ?>; font-weight: bold;">
            <?= htmlspecialchars($mensaje) ?>
        </p>
    <?php endif; ?>

    <form action="index.php?action=registroUsuario" method="POST">
        <label for="nombre_completo">Nombre Completo *</label>
        <input type="text" id="nombre_completo" name="nombre_completo" placeholder="Ej: Juan Pérez" required>

        <label for="mail">Correo Electrónico *</label>
        <input type="email" id="mail" name="mail" placeholder="correo@ejemplo.com" required>

        <label for="password">Contraseña *</label>
        <input type="password" id="password" name="password" placeholder="Crea una contraseña segura" required>

        <label for="telefono">Teléfono</label>
        <input type="tel" id="telefono" name="telefono" placeholder="600000000">

        <label for="taller_id">ID del Taller asignado *</label>
        <input type="number" id="taller_id" name="taller_id" placeholder="Ej: 1" required>

        <button type="submit">Registrarse</button>
    </form>
    
    <p>¿Ya tienes cuenta? <a href="index.php?action=inicioSesion">Inicia sesión aquí</a></p>
</div>