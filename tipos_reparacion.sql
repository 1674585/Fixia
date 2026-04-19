-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-04-2026 a las 12:18:34
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `fixia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_reparacion`
--

CREATE TABLE `tipos_reparacion` (
  `id` int(11) NOT NULL,
  `taller_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `tipos_reparacion`
--

INSERT INTO `tipos_reparacion` (`id`, `taller_id`, `nombre`, `descripcion`, `fecha_creacion`) VALUES
(1, 1, 'Frenos', NULL, '2026-03-14 14:11:13'),
(2, 1, 'Motor', NULL, '2026-03-14 14:11:13'),
(3, 1, 'Transmisión / Embrague', NULL, '2026-03-14 14:11:13'),
(4, 1, 'Suspensión / Dirección', NULL, '2026-03-14 14:11:13'),
(5, 1, 'Sistema eléctrico', NULL, '2026-03-14 14:11:13'),
(6, 1, 'Aire acondicionado / climatización', NULL, '2026-03-14 14:11:13'),
(7, 1, 'Escape', NULL, '2026-03-14 14:11:13'),
(8, 1, 'Carrocería / pintura', NULL, '2026-03-14 14:11:13'),
(9, 1, 'Neumáticos / ruedas', NULL, '2026-03-14 14:11:13'),
(10, 1, 'Sistema de combustible', NULL, '2026-03-14 14:11:13'),
(11, 1, 'Sistema de refrigeración', NULL, '2026-03-14 14:11:13'),
(12, 1, 'Mantenimiento general / Otros', NULL, '2026-03-14 14:11:13');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tipos_reparacion`
--
ALTER TABLE `tipos_reparacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `taller_id` (`taller_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tipos_reparacion`
--
ALTER TABLE `tipos_reparacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tipos_reparacion`
--
ALTER TABLE `tipos_reparacion`
  ADD CONSTRAINT `tipos_reparacion_ibfk_1` FOREIGN KEY (`taller_id`) REFERENCES `talleres` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
