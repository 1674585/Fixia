<div class="contenedor-registro-vehiculo">
    <h1>Registrar Nuevo Vehículo</h1>
    
    <!-- Mensaje de estado -->
    <?php if (!empty($mensaje)): ?>
        <div class="alerta alerta-<?php echo $tipo_mensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>
    
    <!-- Formulario de registro -->
    <form method="POST" class="formulario-vehiculo">
        
        <fieldset>
            <legend>Datos del Cliente</legend>
            
            <div class="grupo-form">
                <label for="email_cliente">Buscar Cliente *</label>
                <input type="text" id="email_cliente" name="email_cliente" 
                       placeholder="Escribe el email o nombre del cliente..." 
                       autocomplete="off" required>
                <input type="hidden" id="cliente_id" name="cliente_id" value="0">
                <div id="sugerencias-clientes" class="sugerencias-lista"></div>
                <small>Escribe al menos 2 caracteres para buscar</small>
            </div>
            
            <div class="grupo-form">
                <label for="nombre_cliente">Nombre del Cliente</label>
                <input type="text" id="nombre_cliente" name="nombre_cliente" disabled placeholder="Se rellenará automáticamente">
            </div>
        </fieldset>
        
        <fieldset>
            <legend>Datos del Vehículo</legend>
            
            <div class="grupo-form">
                <label for="matricula">Matrícula *</label>
                <input type="text" id="matricula" name="matricula" required 
                       placeholder="Ej: 1234ABC" maxlength="15"
                       style="text-transform: uppercase;">
                <small>La matrícula se convertirá a mayúsculas automáticamente</small>
            </div>
            
            <div class="grupo-form">
                <label for="marca">Marca *</label>
                <select id="marca" name="marca" required>
                    <option value="">-- Selecciona una marca --</option>
                    <?php foreach ($marcas as $marca): ?>
                        <option 
                            value="<?php echo htmlspecialchars($marca['nombre']); ?>"
                            data-marca-id="<?php echo $marca['marca_id']; ?>"
                            <?php echo (isset($datos['marca']) && $datos['marca'] === $marca['nombre']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($marca['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grupo-form">
                <label for="modelo">Modelo *</label>
                <select id="modelo" name="modelo" required>
                    <option value="">-- Selecciona primero la marca --</option>
                </select>
            </div>

            
            <div class="grupo-form">
                <label for="anio">Año</label>
                <input type="number" id="anio" name="anio" 
                       min="1900" max="<?php echo date('Y'); ?>" 
                       value="<?php echo date('Y'); ?>"
                       placeholder="Ej: 2023">
            </div>
            
            <div class="grupo-form">
                <label for="kilometraje">Kilometraje Actual</label>
                <input type="number" id="kilometraje" name="kilometraje" 
                       min="0" value="0"
                       placeholder="Ej: 45000">
                <small>En kilómetros</small>
            </div>
        </fieldset>
        
        <div class="botones-form">
            <button type="submit" class="boton boton-exito">
                ✓ Registrar Vehículo
            </button>
            <a href="index.php?action=misVehiculos" class="boton boton-secundario">
                ← Cancelar
            </a>
        </div>
    </form>
</div>

<script>
let timeoutBusqueda;

document.getElementById('email_cliente').addEventListener('input', function() {
    clearTimeout(timeoutBusqueda);
    const query = this.value.trim();
    
    if (query.length >= 2) {
        timeoutBusqueda = setTimeout(() => buscarClientes(query), 300);
    } else {
        ocultarSugerencias();
    }
});

document.getElementById('email_cliente').addEventListener('focus', function() {
    if (this.value.trim().length >= 2) {
        buscarClientes(this.value.trim());
    }
});

function buscarClientes(query) {
    fetch(`index.php?action=buscarClientes&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => mostrarSugerencias(data))
        .catch(error => console.error('Error:', error));
}

function mostrarSugerencias(clientes) {
    const lista = document.getElementById('sugerencias-clientes');
    lista.innerHTML = '';
    
    if (clientes.length === 0) {
        lista.style.display = 'none';
        return;
    }
    
    clientes.forEach(cliente => {
        const item = document.createElement('div');
        item.className = 'sugerencia-item';
        item.onclick = () => seleccionarCliente(cliente);
        
        item.innerHTML = `
            <div class="sugerencia-nombre">${cliente.nombre_completo}</div>
            <div class="sugerencia-email">${cliente.email}</div>
        `;
        
        lista.appendChild(item);
    });
    
    lista.style.display = 'block';
}

function seleccionarCliente(cliente) {
    document.getElementById('email_cliente').value = cliente.email;
    document.getElementById('cliente_id').value = cliente.id;
    document.getElementById('nombre_cliente').value = cliente.nombre_completo;
    ocultarSugerencias();
}

function ocultarSugerencias() {
    document.getElementById('sugerencias-clientes').style.display = 'none';
}

// Ocultar sugerencias al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!document.getElementById('email_cliente').contains(e.target) && 
        !document.getElementById('sugerencias-clientes').contains(e.target)) {
        ocultarSugerencias();
    }
});

function cargarModelos(marcaId, seleccionado = '') {
    console.log('marcaId:', marcaId);
    const modeloSelect = document.getElementById('modelo');
    modeloSelect.innerHTML = '<option value="">Cargando modelos...</option>';
    fetch(`index.php?action=obtenerModelos&marcaId=${encodeURIComponent(marcaId)}`)
        .then(response => response.json())
        .then(modelos => {
            modeloSelect.innerHTML = '<option value="">-- Selecciona un modelo --</option>';
            modelos.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.nombre;
                opt.textContent = m.nombre;
                if (m.nombre === seleccionado) opt.selected = true;
                modeloSelect.appendChild(opt);
            });
        })
        .catch(() => {
            modeloSelect.innerHTML = '<option value="">Error al cargar modelos</option>';
        });
}

document.getElementById('marca').addEventListener('change', function() {
    if (this.value) {
        const marcaId = this.selectedOptions[0].dataset.marcaId;
        cargarModelos(marcaId);
    } else {
        document.getElementById('modelo').innerHTML = '<option value="">-- Selecciona primero la marca --</option>';
    }
});
</script>