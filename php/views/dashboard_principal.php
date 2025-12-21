<?php
include("../includes/conexion.php");

$solo_admin = true;
include("../includes/verificar_acceso.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Principal</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/dashboard.css">  
</head>
<body>
    <!-- Header -->
    <?php include('includes/header.php'); ?>

    <!-- Sidebar -->
    <?php include('includes/sidebar.php'); ?>

    <!-- Datos del dashboard -->
    <?php include("../controllers/dashboard_data.php");?>

    <!-- Contenido principal -->
    <main class="main-content" id="mainContent">
        <!-- Botón volver -->
        <div class="back-container">
            <a href="vista_admin.php" class="back-button">
                <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
            </a>
        </div>

        <!-- Título -->
        <div class="dashboard-header-title">
            <h1>Dashboard Principal</h1>
        </div>

        <!-- Fila 1 -->
        <div class="dashboard-grid grid-1x3">
            <!-- 1. Personal por empresa -->
            <div class="card">
                <h3>Cantidad de Personal por Empresa</h3>
                <?php foreach ($personal_empresa as $item): ?>
                    <div class="empresa-personal-row">
                        <span class="empresa-nombre"><?php echo htmlspecialchars($item['empresa']); ?></span>
                        <span class="empresa-cantidad"><?php echo intval($item['cantidad']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 2. Activos por tipo -->
            <div class="card">
                <h3>Cantidad de Activos por Tipo</h3>
                <div id="chart-activos-tipo"></div>
            </div>

            <!-- 3. Activos en almacén por localidad -->
            <div class="card">
                <h3>Activos en Almacén por Localidad</h3>
                <div id="chart-activos-almacen-localidad"></div>
            </div>
        </div>

        <!-- Fila 2 -->
        <div class="dashboard-grid grid-2x3">
            <!-- 4. Activos por marca y tipo -->
            <div class="card">
                <h3>Activos por Marca y Tipo</h3>
                <div id="chart-activos-marca-tipo"></div>
                <div class="controls">
                    <button type="button" class="btn-marca-tipo active" data-tipo="Laptop">Laptops</button>
                    <button type="button" class="btn-marca-tipo" data-tipo="PC">PCs</button>
                    <button type="button" class="btn-marca-tipo" data-tipo="Servidor">Servidores</button>
                </div>
            </div>

            <!-- 5. Activos por área y tipo -->
            <div class="card">
                <h3>Activos por Área y Tipo</h3>
                <div id="chart-activos-por-area"></div>
                <div class="controls">
                    <button type="button" class="btn-area-tipo active" data-tipo="Laptop">Laptops</button>
                    <button type="button" class="btn-area-tipo" data-tipo="PC">PCs</button>
                </div>
            </div>

            <!-- 6. Activos por estado -->
            <div class="card">
                <h3>Activos por Estado</h3>
                <div id="chart-activos-estado"></div>
                <div class="controls">
                    <button type="button" class="btn-activos-estado active" data-tipo="Laptop">Laptops</button>
                    <button type="button" class="btn-activos-estado" data-tipo="PC">PCs</button>
                    <button type="button" class="btn-activos-estado" data-tipo="Servidor">Servidores</button>
                </div>
            </div>
        </div>

        <!-- Fila 3 -->
        <div class="dashboard-grid grid-3x3">
            <!-- 7. Periféricos por tipo y estado -->
            <div class="card">
                <h3>Periféricos por Tipo y Estado</h3>
                <div id="chart-perifericos-tipo"></div>
                <div class="controls">
                    <button type="button" class="btn-perifericos-tipo active" data-estado="Disponible">Disponible</button>
                    <button type="button" class="btn-perifericos-tipo" data-estado="Asignado">Asignado</button>
                    <button type="button" class="btn-perifericos-tipo" data-estado="Malogrado">Malogrado</button>
                    <button type="button" class="btn-perifericos-tipo" data-estado="Almacen">Almacen</button>
                </div>
            </div>

            <!-- 8. Costo y cantidad de reparaciones -->
            <div class="card">
                <h3>Reparaciones (Costo y Cantidad)</h3>
                <div id="chart-costo-reparacion"></div>
                <div class="controls">
                    <select id="select-costo-mes">
                        <?php
                        $meses_es = [
                            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                        ];
                        for ($m = 1; $m <= 12; $m++) {
                            echo '<option value="'.$m.'"'.($m==$selected_month?' selected':'').'>'.$meses_es[$m].'</option>';
                        }
                        ?>
                    </select>
                    <select id="select-costo-anio">
                        <?php foreach ($anios_reparacion as $anio): ?>
                            <option value="<?php echo $anio; ?>"<?php if ($anio==$selected_year) echo ' selected'; ?>><?php echo $anio; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- 9. Cambio de hardware -->
            <div class="card">
                <h3>Cambio de Hardware (Costo y Cantidad)</h3>
                <div id="chart-costo-cambiohw"></div>
                <div class="controls">
                    <select id="select-cambiohw-mes">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            echo '<option value="'.$m.'"'.($m==$selected_month?' selected':'').'>'.$meses_es[$m].'</option>';
                        }
                        ?>
                    </select>
                    <select id="select-cambiohw-anio">
                        <?php foreach ($anios_reparacion as $anio): ?>
                            <option value="<?php echo $anio; ?>"<?php if ($anio==$selected_year) echo ' selected'; ?>><?php echo $anio; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Fila 4 -->
        <div class="dashboard-grid grid-4x3">
            <!-- 10. Estado de periféricos -->
            <div class="card">
                <h3>Estado de Periféricos</h3>
                <div id="chart-estado-perifericos"></div>
            </div>

            <!-- 11. Tendencia de compra de activos -->
            <div class="card">
                <h3>Tendencia de Compra de Activos</h3>
                <div id="chart-tendencia-compra"></div>
                <div class="controls">
                    <select id="select-tendencia-mes">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            echo '<option value="'.$m.'"'.($m==$selected_month?' selected':'').'>'.$meses_es[$m].'</option>';
                        }
                        ?>
                    </select>
                    <select id="select-tendencia-anio">
                        <?php foreach ($anios_reparacion as $anio): ?>
                            <option value="<?php echo $anio; ?>"<?php if ($anio==$selected_year) echo ' selected'; ?>><?php echo $anio; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Pasar datos PHP a JS -->
    <script>
        var activosTipoData = <?php echo json_encode($activos_tipo); ?>;
        var perifericosEstadoData = <?php echo json_encode($perifericos_estado); ?>;
        var activosAlmacenLocalidadData = <?php echo json_encode($activos_almacen_localidad); ?>;
        var activosMarcaTipoData = <?php echo json_encode($activos_marca_tipo); ?>;
        var activosEstadoTipoData = <?php echo json_encode($activos_estado_tipo); ?>;
        var activosPorAreaTipoData = <?php echo json_encode($activos_por_area_tipo); ?>;
        var perifericosTipoEstadoData = <?php echo json_encode($perifericos_tipo_estado); ?>;
        var costosReparacionTipoData = <?php echo json_encode($costos_reparacion_tipo); ?>;
        var cantReparacionTipoData = <?php echo json_encode($cant_reparacion_tipo); ?>;
        var costosCambioHWTipoData = <?php echo json_encode($costos_cambio_hw_tipo); ?>;
        var cantCambioHWTipoData = <?php echo json_encode($cant_cambio_hw_tipo); ?>;
        var selectedCostoMes = <?php echo $selected_month; ?>;
        var selectedCostoAnio = <?php echo $selected_year; ?>;
        var selectedCambioHWMes = <?php echo $selected_month; ?>;
        var selectedCambioHWAnio = <?php echo $selected_year; ?>;
        var tendenciaCompraActivosData = <?php echo json_encode($tendencia_compra_activos); ?>;
        var selectedTendenciaMes = <?php echo $selected_month; ?>;
        var selectedTendenciaAnio = <?php echo $selected_year; ?>;
    </script>

    <!-- Scripts -->
<script src="../../js/admin/sidebar.js"></script>
<script src="../../js/libs/echarts-master/dist/echarts.min.js"></script>
<script src="../../js/dashboards/echarts.js"></script>

</body>
</html>
