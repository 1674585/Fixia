-- Desactivar revisión de llaves para una creación limpia
SET FOREIGN_KEY_CHECKS = 0;

-- 1. TABLA DE TALLERES (TENANTS)
CREATE TABLE talleres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    identificacion_fiscal VARCHAR(20) UNIQUE, -- CIF/NIF
    direccion TEXT,
    tarifa_hora_base DECIMAL(10,2) DEFAULT 45.00,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. USUARIOS (Jefes, Recepcionistas, Mecánicos, Clientes)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taller_id INT NOT NULL,
    rol ENUM('ceo','jefe', 'recepcionista', 'mecanico', 'cliente') NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    FOREIGN KEY (taller_id) REFERENCES talleres(id) ON DELETE CASCADE,
    UNIQUE(email, taller_id)
) ENGINE=InnoDB;

-- 3. VEHÍCULOS
CREATE TABLE vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taller_id INT NOT NULL,
    cliente_id INT NOT NULL,
    matricula VARCHAR(15) NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    anio INT,
    ultimo_kilometraje INT DEFAULT 0,
    FOREIGN KEY (taller_id) REFERENCES talleres(id),
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- 4. CATÁLOGO DE TAREAS (ADN para la IA)
CREATE TABLE catalogo_tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taller_id INT NOT NULL,
    nombre_tarea VARCHAR(100) NOT NULL,
    minutos_estimados_base INT,
    FOREIGN KEY (taller_id) REFERENCES talleres(id)
) ENGINE=InnoDB;

-- 5. INVENTARIO DE PRODUCTOS / REPUESTOS
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taller_id INT NOT NULL,
    referencia_sku VARCHAR(50), 
    nombre VARCHAR(100) NOT NULL,
    cantidad_stock INT DEFAULT 0,
    alerta_stock_minimo INT DEFAULT 5,
    precio_venta DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (taller_id) REFERENCES talleres(id)
) ENGINE=InnoDB;

-- 6. ÓRDENES DE TRABAJO
CREATE TABLE ordenes_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taller_id INT NOT NULL,
    vehiculo_id INT NOT NULL,
    creado_por_id INT NOT NULL,    -- Recepcionista
    supervisor_id INT,             -- Jefe que asigna
    estado ENUM('recibido', 'diagnosticando', 'presupuestado', 'en_reparacion', 'listo', 'facturado') DEFAULT 'recibido',
    sintomas_cliente TEXT,
    diagnostico_tecnico TEXT,
    precio_estimado_ia DECIMAL(10,2),
    tiempo_estimado_ia INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (taller_id) REFERENCES talleres(id),
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id),
    FOREIGN KEY (creado_por_id) REFERENCES usuarios(id),
    FOREIGN KEY (supervisor_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- 7. ASIGNACIÓN DE TAREAS ESPECÍFICAS
CREATE TABLE tareas_asignadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_trabajo_id INT NOT NULL,
    tarea_catalogo_id INT NOT NULL,
    mecanico_id INT NOT NULL,
    hora_inicio DATETIME,
    hora_fin DATETIME,
    duracion_real_minutos INT,    -- Dato clave para entrenar IA
    estado ENUM('pendiente', 'en_proceso', 'finalizada') DEFAULT 'pendiente',
    FOREIGN KEY (orden_trabajo_id) REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    FOREIGN KEY (tarea_catalogo_id) REFERENCES catalogo_tareas(id),
    FOREIGN KEY (mecanico_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- 8. REPUESTOS CONSUMIDOS POR CADA TAREA
CREATE TABLE repuestos_tarea (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tarea_asignada_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unidad_momento DECIMAL(10,2),
    FOREIGN KEY (tarea_asignada_id) REFERENCES tareas_asignadas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;