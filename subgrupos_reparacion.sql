-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-04-2026 a las 23:20:44
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
-- Estructura de tabla para la tabla `subgrupos_reparacion`
--

CREATE TABLE `subgrupos_reparacion` (
  `id` int(11) NOT NULL,
  `tipo_reparacion_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `minutos_estimados_base` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `subgrupos_reparacion`
--

INSERT INTO `subgrupos_reparacion` (`id`, `tipo_reparacion_id`, `nombre`, `descripcion`, `minutos_estimados_base`) VALUES
(1, 1, 'Cambio de pastillas', NULL, NULL),
(2, 1, 'Cambio de discos', NULL, NULL),
(3, 1, 'Sustitución de líquido de frenos', NULL, NULL),
(4, 1, 'Reparación de pinzas', NULL, NULL),
(5, 1, 'Cambio de zapatas / tambores', NULL, NULL),
(6, 1, 'Comprobación y ajuste de frenos de mano', NULL, NULL),
(7, 2, 'Cambio de aceite y filtro', NULL, NULL),
(8, 2, 'Sustitución de correas (distribución, accesorios)', NULL, NULL),
(9, 2, 'Cambio de bujías', NULL, NULL),
(10, 2, 'Sustitución de filtro de aire', NULL, NULL),
(11, 2, 'Sustitución de filtro de combustible', NULL, NULL),
(12, 2, 'Reparación de culata / juntas', NULL, NULL),
(13, 2, 'Limpieza de inyectores', NULL, NULL),
(14, 2, 'Diagnóstico electrónico del motor', NULL, NULL),
(15, 3, 'Cambio de embrague', NULL, NULL),
(16, 3, 'Sustitución de volante bimasa', NULL, NULL),
(17, 3, 'Cambio de aceite de caja de cambios manual', NULL, NULL),
(18, 3, 'Cambio de aceite de caja automática', NULL, NULL),
(19, 3, 'Reparación de diferencial', NULL, NULL),
(20, 3, 'Reparación de palieres y homocinéticas', NULL, NULL),
(21, 4, 'Sustitución de amortiguadores', NULL, NULL),
(22, 4, 'Sustitución de muelles / ballestas', NULL, NULL),
(23, 4, 'Cambio de brazos / bieletas', NULL, NULL),
(24, 4, 'Sustitución de rótulas', NULL, NULL),
(25, 4, 'Alineación de ruedas / geometría', NULL, NULL),
(26, 4, 'Cambio de barras estabilizadoras', NULL, NULL),
(27, 5, 'Sustitución de batería', NULL, NULL),
(28, 5, 'Reparación alternador / motor de arranque', NULL, NULL),
(29, 5, 'Sustitución de luces', NULL, NULL),
(30, 5, 'Diagnóstico electrónico de averías', NULL, NULL),
(31, 5, 'Sustitución de fusibles y relés', NULL, NULL),
(32, 5, 'Reparación de centralitas y módulos', NULL, NULL),
(33, 6, 'Recarga gas A/C', NULL, NULL),
(34, 6, 'Sustitución de compresor', NULL, NULL),
(35, 6, 'Sustitución de filtro de habitáculo', NULL, NULL),
(36, 6, 'Reparación de evaporador / condensador', NULL, NULL),
(37, 6, 'Comprobación de fugas y presión', NULL, NULL),
(38, 7, 'Cambio de silencioso / tramo de escape', NULL, NULL),
(39, 7, 'Sustitución de catalizador', NULL, NULL),
(40, 7, 'Sustitución de sondas lambda', NULL, NULL),
(41, 7, 'Reparación de tubos y soportes', NULL, NULL),
(42, 8, 'Reparación de abolladuras', NULL, NULL),
(43, 8, 'Sustitución de puertas, paragolpes, capó', NULL, NULL),
(44, 8, 'Pintura parcial / completa', NULL, NULL),
(45, 8, 'Pulido y retoque de pintura', NULL, NULL),
(46, 8, 'Sustitución de lunas', NULL, NULL),
(47, 9, 'Sustitución de neumáticos', NULL, NULL),
(48, 9, 'Montaje / equilibrado', NULL, NULL),
(49, 9, 'Reparación de pinchazos', NULL, NULL),
(50, 9, 'Cambio de llantas', NULL, NULL),
(51, 9, 'Alineación y paralelismo', NULL, NULL),
(52, 10, 'Sustitución de bomba de combustible', NULL, NULL),
(53, 10, 'Limpieza o cambio de inyectores', NULL, NULL),
(54, 10, 'Sustitución de filtro de combustible', NULL, NULL),
(55, 10, 'Reparación depósito y conductos', NULL, NULL),
(56, 11, 'Sustitución de radiador', NULL, NULL),
(57, 11, 'Sustitución de bomba de agua', NULL, NULL),
(58, 11, 'Sustitución de termostato', NULL, NULL),
(59, 11, 'Cambio de líquido refrigerante', NULL, NULL),
(60, 11, 'Reparación manguitos y abrazaderas', NULL, NULL),
(61, 12, 'Inspección pre-ITV', NULL, NULL),
(62, 12, 'Revisión 10.000 / 20.000 km', NULL, NULL),
(63, 12, 'Cambio de limpiaparabrisas', NULL, NULL),
(64, 12, 'Comprobación de niveles y líquidos', NULL, NULL),
(65, 12, 'Lavado interior / exterior', NULL, NULL),
(66, 12, 'Diagnóstico', NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `subgrupos_reparacion`
--
ALTER TABLE `subgrupos_reparacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_reparacion_id` (`tipo_reparacion_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `subgrupos_reparacion`
--
ALTER TABLE `subgrupos_reparacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `subgrupos_reparacion`
--
ALTER TABLE `subgrupos_reparacion`
  ADD CONSTRAINT `subgrupos_reparacion_ibfk_1` FOREIGN KEY (`tipo_reparacion_id`) REFERENCES `tipos_reparacion` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
