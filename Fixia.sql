-- Desactivar revisión de llaves para una creación limpia
SET FOREIGN_KEY_CHECKS = 0;

-- 1. TABLA DE TALLERES (TENANTS)
CREATE TABLE `talleres` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `identificacion_fiscal` varchar(20) DEFAULT NULL,
    `direccion` text DEFAULT NULL,
    `tarifa_hora_base` decimal(10, 2) DEFAULT 45.00,
    `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `identificacion_fiscal` (`identificacion_fiscal`)
) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_spanish2_ci

-- 2. USUARIOS (Jefes, Recepcionistas, Mecánicos, Clientes)
CREATE TABLE `usuarios` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `taller_id` int(11) NOT NULL,
    `rol` enum(
        'ceo',
        'jefe',
        'recepcionista',
        'mecanico',
        'cliente'
    ) NOT NULL,
    `nombre_completo` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `telefono` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`, `taller_id`),
    KEY `taller_id` (`taller_id`),
    CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`taller_id`) REFERENCES `talleres` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 5123 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_spanish2_ci

-- 3. VEHÍCULOS
CREATE TABLE `vehiculos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `taller_id` int(11) NOT NULL,
    `cliente_id` int(11) NOT NULL,
    `matricula` varchar(15) NOT NULL,
    `marca` varchar(50) NOT NULL,
    `modelo` varchar(50) NOT NULL,
    `anio` int(11) DEFAULT NULL,
    `ultimo_kilometraje` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `taller_id` (`taller_id`),
    KEY `cliente_id` (`cliente_id`),
    CONSTRAINT `vehiculos_ibfk_1` FOREIGN KEY (`taller_id`) REFERENCES `talleres` (`id`),
    CONSTRAINT `vehiculos_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`)
) ENGINE = InnoDB AUTO_INCREMENT = 4406 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_spanish2_ci

-- 4. CATÁLOGO DE TAREAS (ADN para la IA)
CREATE TABLE `catalogo_tareas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `taller_id` int(11) NOT NULL,
    `nombre_tarea` varchar(100) NOT NULL,
    `minutos_estimados_base` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `taller_id` (`taller_id`),
    CONSTRAINT `catalogo_tareas_ibfk_1` FOREIGN KEY (`taller_id`) REFERENCES `talleres` (`id`)
) ENGINE = InnoDB AUTO_INCREMENT = 14 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_spanish2_ci

-- 5. INVENTARIO DE PRODUCTOS / REPUESTOS
CREATE TABLE `productos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `taller_id` int(11) NOT NULL,
    `referencia_sku` varchar(50) DEFAULT NULL,
    `nombre` varchar(100) NOT NULL,
    `cantidad_stock` int(11) DEFAULT 0,
    `alerta_stock_minimo` int(11) DEFAULT 5,
    `precio_venta` decimal(10, 2) NOT NULL,
    `precio_compra` decimal(10, 2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `taller_id` (`taller_id`),
    CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`taller_id`) REFERENCES `talleres` (`id`)
) ENGINE = InnoDB AUTO_INCREMENT = 117 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_spanish2_ci

-- 6. ÓRDENES DE TRABAJO
CREATE TABLE `ordenes_trabajo` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `taller_id` int(11) NOT NULL,
    `vehiculo_id` int(11) NOT NULL,
    `creado_por_id` int(11) NOT NULL,
    `supervisor_id` int(11) DEFAULT NULL,
    `estado` enum(
        'recibido',
        'diagnosticando',
        'presupuestado',
        'en_reparacion',
        'listo',
        'facturado'
    ) DEFAULT 'recibido',
    `sintomas_cliente` text DEFAULT NULL,
    `diagnostico_tecnico` text DEFAULT NULL,
    `precio_estimado_ia` decimal(10, 2) DEFAULT NULL,
    `tiempo_estimado_ia` int(11) DEFAULT NULL,
    `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
    `asignado_a_id` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `taller_id` (`taller_id`),
    KEY `vehiculo_id` (`vehiculo_id`),
    KEY `creado_por_id` (`creado_por_id`),
    KEY `supervisor_id` (`supervisor_id`),
    KEY `fk_orden_asignado_a` (`asignado_a_id`),
    CONSTRAINT `fk_orden_asignado_a` FOREIGN KEY (`asignado_a_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `ordenes_trabajo_ibfk_1` FOREIGN KEY (`taller_id`) REFERENCES `talleres` (`id`),
    CONSTRAINT `ordenes_trabajo_ibfk_2` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`),
    CONSTRAINT `ordenes_trabajo_ibfk_3` FOREIGN KEY (`creado_por_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `ordenes_trabajo_ibfk_4` FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id`)
) ENGINE = InnoDB AUTO_INCREMENT = 15 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_spanish2_ci

-- 7. ASIGNACIÓN DE TAREAS ESPECÍFICAS
CREATE TABLE `tareas_asignadas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `orden_trabajo_id` int(11) NOT NULL,
    `tarea_catalogo_id` int(11) NOT NULL,
    `mecanico_id` int(11) DEFAULT NULL,
    `hora_inicio` datetime DEFAULT NULL,
    `hora_fin` datetime DEFAULT NULL,
    `duracion_real_minutos` int(11) DEFAULT NULL,
    `estado` enum(
        'pendiente',
        'en_proceso',
        'finalizada'
    ) DEFAULT 'pendiente',
    PRIMARY KEY (`id`),
    KEY `orden_trabajo_id` (`orden_trabajo_id`),
    KEY `tarea_catalogo_id` (`tarea_catalogo_id`),
    KEY `mecanico_id` (`mecanico_id`),
    CONSTRAINT `tareas_asignadas_ibfk_1` FOREIGN KEY (`orden_trabajo_id`) REFERENCES `ordenes_trabajo` (`id`) ON DELETE CASCADE,
    CONSTRAINT `tareas_asignadas_ibfk_2` FOREIGN KEY (`tarea_catalogo_id`) REFERENCES `catalogo_tareas` (`id`),
    CONSTRAINT `tareas_asignadas_ibfk_3` FOREIGN KEY (`mecanico_id`) REFERENCES `usuarios` (`id`)
) ENGINE = InnoDB AUTO_INCREMENT = 16 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_spanish2_ci

-- 8. REPUESTOS CONSUMIDOS POR CADA TAREA
CREATE TABLE `repuestos_tarea` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tarea_asignada_id` int(11) NOT NULL,
    `producto_id` int(11) NOT NULL,
    `cantidad` int(11) NOT NULL,
    `precio_unidad_momento` decimal(10, 2) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `tarea_asignada_id` (`tarea_asignada_id`),
    KEY `producto_id` (`producto_id`),
    CONSTRAINT `repuestos_tarea_ibfk_1` FOREIGN KEY (`tarea_asignada_id`) REFERENCES `tareas_asignadas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `repuestos_tarea_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE = InnoDB AUTO_INCREMENT = 12 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_spanish2_ci

-- 1️⃣ Tabla de tipos de reparación (categoría principal)
CREATE TABLE `tipos_reparacion` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `taller_id` int(11) NOT NULL,
    `nombre` varchar(100) NOT NULL,
    `descripcion` text DEFAULT NULL,
    `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `taller_id` (`taller_id`),
    CONSTRAINT `tipos_reparacion_ibfk_1` FOREIGN KEY (`taller_id`) REFERENCES `talleres` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 13 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_spanish2_ci

-- 2️⃣ Tabla de subgrupos de reparación (subtareas)
CREATE TABLE `subgrupos_reparacion` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tipo_reparacion_id` int(11) NOT NULL,
    `nombre` varchar(100) NOT NULL,
    `descripcion` text DEFAULT NULL,
    `minutos_estimados_base` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `tipo_reparacion_id` (`tipo_reparacion_id`),
    CONSTRAINT `subgrupos_reparacion_ibfk_1` FOREIGN KEY (`tipo_reparacion_id`) REFERENCES `tipos_reparacion` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 67 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_spanish2_ci