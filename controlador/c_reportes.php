<?php
    // ─────────────────────────────────────────────
    // Controlador: Reportes y métricas de negocio
    // Solo accesible por ceo y jefe
    // ─────────────────────────────────────────────

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['taller_id'])) {
        header("Location: index.php?action=home");
        exit;
    }

    if (!in_array($_SESSION['rol'], ['ceo', 'jefe'])) {
        header("Location: index.php?action=home");
        exit;
    }

    require_once __DIR__ . '/../modelo/m_reportes.php';

    $taller_id = (int)$_SESSION['taller_id'];

    // ── Filtros ────────────────────────────────────
    // Período predefinido
    $periodo = $_GET['periodo'] ?? '30dias';

    // Calcular fechas según período predefinido
    $hoy = date('Y-m-d');
    switch ($periodo) {
        case '7dias':
            $fecha_desde_default = date('Y-m-d', strtotime('-7 days'));
            $fecha_hasta_default = $hoy;
            break;
        case '30dias':
            $fecha_desde_default = date('Y-m-d', strtotime('-30 days'));
            $fecha_hasta_default = $hoy;
            break;
        case 'mes_actual':
            $fecha_desde_default = date('Y-m-01');
            $fecha_hasta_default = $hoy;
            break;
        case 'mes_anterior':
            $fecha_desde_default = date('Y-m-01', strtotime('first day of last month'));
            $fecha_hasta_default = date('Y-m-t',  strtotime('last day of last month'));
            break;
        case 'anio_actual':
            $fecha_desde_default = date('Y-01-01');
            $fecha_hasta_default = $hoy;
            break;
        case 'personalizado':
            $fecha_desde_default = '';
            $fecha_hasta_default = '';
            break;
        default:
            $fecha_desde_default = date('Y-m-d', strtotime('-30 days'));
            $fecha_hasta_default = $hoy;
    }

    // Si es personalizado, usar los campos manuales; si no, usar los del período
    $fecha_desde = $periodo === 'personalizado'
        ? ($_GET['desde'] ?? '')
        : $fecha_desde_default;
    $fecha_hasta = $periodo === 'personalizado'
        ? ($_GET['hasta'] ?? '')
        : $fecha_hasta_default;

    // Agrupación para gráficos temporales
    $agrupacion = $_GET['agrupacion'] ?? 'dia';
    if (!in_array($agrupacion, ['dia', 'semana', 'mes'])) {
        $agrupacion = 'dia';
    }

    // Filtro por mecánico (para sección de equipo)
    $mecanico_filtro = isset($_GET['mecanico_id']) ? (int)$_GET['mecanico_id'] : 0;

    // ── Cargar todas las métricas ──────────────────
    $ordenes_por_estado   = reporteOrdenesPorEstado($taller_id, $fecha_desde, $fecha_hasta);
    $tiempo_medio         = reporteTiempoMedioReparacion($taller_id, $fecha_desde, $fecha_hasta);
    $throughput           = reporteThroughput($taller_id, $fecha_desde, $fecha_hasta, $agrupacion);
    $financiero           = reporteFinanciero($taller_id, $fecha_desde, $fecha_hasta);
    $ingresos_periodo     = reporteIngresosPorPeriodo($taller_id, $fecha_desde, $fecha_hasta, $agrupacion);
    $productividad        = reporteProductividadMecanicos($taller_id, $fecha_desde, $fecha_hasta);
    $stock_critico        = reporteStockCritico($taller_id);
    $valor_stock          = reporteValorStock($taller_id);
    $productos_mas_usados = reporteProductosMasUsados($taller_id, $fecha_desde, $fecha_hasta);

    require_once __DIR__ . '/../vista/v_reportes.php';
?>