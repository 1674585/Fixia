<?php
    // ─────────────────────────────────────────────
    // Controlador: Reportes y métricas del taller
    // Solo accesible por ceo y jefe.
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

    // ── Período predefinido (preset) ───────────────
    $presets = ['hoy','7dias','30dias','mes_actual','mes_anterior','anio_actual','personalizado'];
    $periodo = $_GET['periodo'] ?? '30dias';
    if (!in_array($periodo, $presets, true)) $periodo = '30dias';

    $hoy = date('Y-m-d');

    switch ($periodo) {
        case 'hoy':
            $fecha_desde = $hoy;
            $fecha_hasta = $hoy;
            break;
        case '7dias':
            $fecha_desde = date('Y-m-d', strtotime('-6 days'));
            $fecha_hasta = $hoy;
            break;
        case 'mes_actual':
            $fecha_desde = date('Y-m-01');
            $fecha_hasta = $hoy;
            break;
        case 'mes_anterior':
            $fecha_desde = date('Y-m-01', strtotime('first day of last month'));
            $fecha_hasta = date('Y-m-t',  strtotime('last day of last month'));
            break;
        case 'anio_actual':
            $fecha_desde = date('Y-01-01');
            $fecha_hasta = $hoy;
            break;
        case 'personalizado':
            $fecha_desde = $_GET['desde'] ?? '';
            $fecha_hasta = $_GET['hasta'] ?? '';
            break;
        case '30dias':
        default:
            $fecha_desde = date('Y-m-d', strtotime('-29 days'));
            $fecha_hasta = $hoy;
    }

    // Validar formato Y-m-d
    foreach (['fecha_desde', 'fecha_hasta'] as $v) {
        if ($$v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $$v)) {
            $$v = '';
        }
    }

    // Si desde > hasta los intercambiamos (defensa contra input incoherente)
    if ($fecha_desde && $fecha_hasta && $fecha_desde > $fecha_hasta) {
        [$fecha_desde, $fecha_hasta] = [$fecha_hasta, $fecha_desde];
    }

    // ── Agrupación temporal de los gráficos ────────
    $agrupacion = $_GET['agrupacion'] ?? 'dia';
    if (!in_array($agrupacion, ['dia', 'semana', 'mes'], true)) {
        $agrupacion = 'dia';
    }

    // ── Cargar todas las métricas ──────────────────
    $financiero        = r_kpis_financieros($taller_id, $fecha_desde, $fecha_hasta);
    $ingresos_periodo  = r_ingresos_por_periodo($taller_id, $fecha_desde, $fecha_hasta, $agrupacion);
    $ordenes_estado    = r_ordenes_por_estado($taller_id, $fecha_desde, $fecha_hasta);
    $tiempo_medio      = r_tiempo_medio($taller_id, $fecha_desde, $fecha_hasta);
    $precision_ia      = r_precision_ia($taller_id, $fecha_desde, $fecha_hasta);
    $presupuestos      = r_aprobacion_presupuestos($taller_id, $fecha_desde, $fecha_hasta);
    $productividad     = r_productividad_mecanicos($taller_id, $fecha_desde, $fecha_hasta);
    $stock_critico     = r_stock_critico($taller_id);
    $valor_stock       = r_valor_stock($taller_id);
    $top_materiales    = r_top_materiales($taller_id, $fecha_desde, $fecha_hasta);
    $backlog           = r_backlog($taller_id);

    require_once __DIR__ . '/../vista/v_reportes.php';
?>
