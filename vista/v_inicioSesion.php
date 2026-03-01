<div class="login">
    <h1>Iniciar Sesión</h1>

    <?php if ($mensajeError !== ''): ?>
        <p class="error"><?= htmlspecialchars($mensajeError) ?></p>
    <?php endif; ?>

    <form action="index.php?action=inicioSesion" method="POST">
        <label for="mail">Correo Electrónico</label>
        <input type="email" id="mail" name="mail" placeholder="Introduce el correo" required>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" placeholder="Introduce la contraseña" required>

        <button type="submit">Iniciar Sesión</button>
    </form>
</div>