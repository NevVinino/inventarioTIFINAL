<?php
// Este controlador prepara todos los datasets requeridos por el dashboard.
// Requiere que exista $conn (conexion.php) antes de ser incluido.

// 1) Activos por tipo (pie)
$activos_tipo = [];
$tipos = ['Laptop' => 'id_laptop', 'PC' => 'id_pc', 'Servidor' => 'id_servidor'];
foreach ($tipos as $tipo => $campo) {
    $sql = "SELECT COUNT(*) as cantidad FROM activo WHERE $campo IS NOT NULL";
    $res = sqlsrv_query($conn, $sql);
    $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
    $activos_tipo[] = ['name' => $tipo, 'value' => intval($row['cantidad'])];
}

// 2) Periféricos por estado (dona global)
$perifericos_estado = [];
$sql = "SELECT ep.vestado_periferico, COUNT(*) as cantidad
        FROM periferico p
        LEFT JOIN estado_periferico ep ON p.id_estado_periferico = ep.id_estado_periferico
        GROUP BY ep.vestado_periferico";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $perifericos_estado[] = ['name' => $row['vestado_periferico'], 'value' => intval($row['cantidad'])];
}

// 3) Cantidad de personal por empresa (lista superior)
$personal_empresa = [];
$sql = "SELECT e.nombre, COALESCE(COUNT(p.id_persona), 0) as cantidad
        FROM empresa e LEFT JOIN persona p ON p.id_empresa = e.id_empresa
        GROUP BY e.nombre";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $personal_empresa[] = ['empresa' => $row['nombre'], 'cantidad' => intval($row['cantidad'])];
}

// 4) Cantidad de activos en almacén por localidad (opcional)
$activos_almacen_localidad = [];
$sql = "SELECT l.localidad_nombre, COUNT(ha.id_activo) as cantidad
        FROM historial_almacen ha
        INNER JOIN almacen a ON ha.id_almacen = a.id_almacen
        INNER JOIN localidad l ON a.id_localidad = l.id_localidad
        WHERE ha.fecha_salida IS NULL
        GROUP BY l.localidad_nombre";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $activos_almacen_localidad[] = [
        'localidad' => $row['localidad_nombre'],
        'cantidad' => intval($row['cantidad'])
    ];
}

// 5) Activos por marca y tipo (barras conmutables)
$activos_marca_tipo = [ 'Laptop' => [], 'PC' => [], 'Servidor' => [] ];
// Laptop
$sql = "SELECT m.nombre as marca, COUNT(l.id_laptop) as cantidad FROM laptop l
        LEFT JOIN marca m ON l.id_marca = m.id_marca GROUP BY m.nombre";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $activos_marca_tipo['Laptop'][] = ['marca' => $row['marca'], 'cantidad' => intval($row['cantidad'])];
}
// PC
$sql = "SELECT m.nombre as marca, COUNT(p.id_pc) as cantidad FROM pc p
        LEFT JOIN marca m ON p.id_marca = m.id_marca GROUP BY m.nombre";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $activos_marca_tipo['PC'][] = ['marca' => $row['marca'], 'cantidad' => intval($row['cantidad'])];
}
// Servidor
$sql = "SELECT m.nombre as marca, COUNT(s.id_servidor) as cantidad FROM servidor s
        LEFT JOIN marca m ON s.id_marca = m.id_marca GROUP BY m.nombre";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $activos_marca_tipo['Servidor'][] = ['marca' => $row['marca'], 'cantidad' => intval($row['cantidad'])];
}

// 6) Activos por estado y tipo (pie conmutables)
$activos_estado_tipo = [ 'Laptop' => [], 'PC' => [], 'Servidor' => [] ];
// Laptop
$sql = "SELECT ea.vestado_activo, COUNT(*) as cantidad
        FROM laptop l LEFT JOIN estado_activo ea ON l.id_estado_activo = ea.id_estado_activo
        GROUP BY ea.vestado_activo";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $activos_estado_tipo['Laptop'][] = ['estado' => $row['vestado_activo'], 'cantidad' => intval($row['cantidad'])];
}
// PC
$sql = "SELECT ea.vestado_activo, COUNT(*) as cantidad
        FROM pc p LEFT JOIN estado_activo ea ON p.id_estado_activo = ea.id_estado_activo
        GROUP BY ea.vestado_activo";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $activos_estado_tipo['PC'][] = ['estado' => $row['vestado_activo'], 'cantidad' => intval($row['cantidad'])];
}
// Servidor
$sql = "SELECT ea.vestado_activo, COUNT(*) as cantidad
        FROM servidor s LEFT JOIN estado_activo ea ON s.id_estado_activo = ea.id_estado_activo
        GROUP BY ea.vestado_activo";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $activos_estado_tipo['Servidor'][] = ['estado' => $row['vestado_activo'], 'cantidad' => intval($row['cantidad'])];
}

// 7) Activos por área y tipo (barras conmutables - solo asignados)
$activos_por_area_tipo = [ 'Laptop' => [], 'PC' => [], 'Servidor' => [] ];
// Laptop
$sql = "SELECT ar.nombre AS area, COUNT(a.id_activo) AS cantidad
        FROM activo a
        LEFT JOIN asignacion asg ON a.id_activo = asg.id_activo
        LEFT JOIN persona p ON asg.id_persona = p.id_persona
        LEFT JOIN area ar ON p.id_area = ar.id_area
        WHERE asg.fecha_retorno IS NULL AND a.id_laptop IS NOT NULL AND asg.id_persona IS NOT NULL
        GROUP BY ar.nombre";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $activos_por_area_tipo['Laptop'][] = ['area' => $row['area'], 'cantidad' => intval($row['cantidad'])];
}
// PC
$sql = "SELECT ar.nombre AS area, COUNT(a.id_activo) AS cantidad
        FROM activo a
        LEFT JOIN asignacion asg ON a.id_activo = asg.id_activo
        LEFT JOIN persona p ON asg.id_persona = p.id_persona
        LEFT JOIN area ar ON p.id_area = ar.id_area
        WHERE asg.fecha_retorno IS NULL AND a.id_pc IS NOT NULL AND asg.id_persona IS NOT NULL
        GROUP BY ar.nombre";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $activos_por_area_tipo['PC'][] = ['area' => $row['area'], 'cantidad' => intval($row['cantidad'])];
}
// Servidor
$sql = "SELECT ar.nombre AS area, COUNT(a.id_activo) AS cantidad
        FROM activo a
        LEFT JOIN asignacion asg ON a.id_activo = asg.id_activo
        LEFT JOIN persona p ON asg.id_persona = p.id_persona
        LEFT JOIN area ar ON p.id_area = ar.id_area
        WHERE asg.fecha_retorno IS NULL AND a.id_servidor IS NOT NULL AND asg.id_persona IS NOT NULL
        GROUP BY ar.nombre";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $activos_por_area_tipo['Servidor'][] = ['area' => $row['area'], 'cantidad' => intval($row['cantidad'])];
}

// 8) Periféricos por tipo y estado (pie conmutables)
$perifericos_tipo_estado = [ 'Disponible' => [], 'Asignado' => [], 'Malogrado' => [], 'Almacen' => [] ];
$estados_periferico = ['Disponible','Asignado','Malogrado','Almacen'];
foreach ($estados_periferico as $estado) {
    $sql = "SELECT tp.vtipo_periferico AS tipo, COUNT(p.id_periferico) AS cantidad
            FROM periferico p
            LEFT JOIN tipo_periferico tp ON p.id_tipo_periferico = tp.id_tipo_periferico
            LEFT JOIN estado_periferico ep ON p.id_estado_periferico = ep.id_estado_periferico
            WHERE ep.vestado_periferico = '$estado'
            GROUP BY tp.vtipo_periferico";
    $res = sqlsrv_query($conn, $sql);
    while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
        // Cambia 'name' y 'value' por 'tipo' y 'cantidad'
        $perifericos_tipo_estado[$estado][] = [
            'tipo' => $row['tipo'],
            'cantidad' => intval($row['cantidad'])
        ];
    }
}

// 9) Mes/Año seleccionados (para filtros en costos y tendencia)
$selected_month = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$selected_year = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

// 10) Costos y cantidad de reparaciones por tipo de activo (mes/año)
$costos_reparacion_tipo = [ 'Laptop' => 0, 'PC' => 0, 'Servidor' => 0 ];
$cant_reparacion_tipo   = [ 'Laptop' => 0, 'PC' => 0, 'Servidor' => 0 ];
foreach (['Laptop','PC','Servidor'] as $tipo) {
    if ($tipo === 'Laptop') { $join = 'LEFT JOIN laptop l ON a.id_laptop = l.id_laptop'; $where = 'a.id_laptop IS NOT NULL'; }
    elseif ($tipo === 'PC') { $join = 'LEFT JOIN pc p ON a.id_pc = p.id_pc'; $where = 'a.id_pc IS NOT NULL'; }
    else { $join = 'LEFT JOIN servidor s ON a.id_servidor = s.id_servidor'; $where = 'a.id_servidor IS NOT NULL'; }
    $sql = "SELECT SUM(r.costo) as total, COUNT(*) as cantidad
            FROM reparacion r INNER JOIN activo a ON r.id_activo = a.id_activo
            $join WHERE $where AND MONTH(r.fecha) = $selected_month AND YEAR(r.fecha) = $selected_year";
    $res = sqlsrv_query($conn, $sql);
    $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
    $costos_reparacion_tipo[$tipo] = floatval($row['total'] ?? 0);
    $cant_reparacion_tipo[$tipo]   = intval($row['cantidad'] ?? 0);
}

// 11) Costos y cantidad de cambios de HW por tipo de activo (mes/año)
$costos_cambio_hw_tipo = [ 'Laptop' => 0, 'PC' => 0, 'Servidor' => 0 ];
$cant_cambio_hw_tipo   = [ 'Laptop' => 0, 'PC' => 0, 'Servidor' => 0 ];
foreach (['Laptop','PC','Servidor'] as $tipo) {
    if ($tipo === 'Laptop') { $join = 'LEFT JOIN laptop l ON a.id_laptop = l.id_laptop'; $where = 'a.id_laptop IS NOT NULL'; }
    elseif ($tipo === 'PC') { $join = 'LEFT JOIN pc p ON a.id_pc = p.id_pc'; $where = 'a.id_pc IS NOT NULL'; }
    else { $join = 'LEFT JOIN servidor s ON a.id_servidor = s.id_servidor'; $where = 'a.id_servidor IS NOT NULL'; }
    $sql = "SELECT SUM(ch.costo) as total, COUNT(*) as cantidad
            FROM cambio_hardware ch INNER JOIN activo a ON ch.id_activo = a.id_activo
            $join WHERE $where AND MONTH(ch.fecha) = $selected_month AND YEAR(ch.fecha) = $selected_year";
    $res = sqlsrv_query($conn, $sql);
    $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
    $costos_cambio_hw_tipo[$tipo] = floatval($row['total'] ?? 0);
    $cant_cambio_hw_tipo[$tipo]   = intval($row['cantidad'] ?? 0);
}

// 12) Años disponibles (reparación, cambio HW y compras) para selects
$anios_reparacion = [];
$sql = "SELECT DISTINCT YEAR(fecha) as anio FROM reparacion WHERE fecha IS NOT NULL
        UNION
        SELECT DISTINCT YEAR(fecha) as anio FROM cambio_hardware WHERE fecha IS NOT NULL
        UNION
        SELECT DISTINCT YEAR(fechaCompra) as anio FROM laptop WHERE fechaCompra IS NOT NULL
        UNION
        SELECT DISTINCT YEAR(fechaCompra) as anio FROM pc WHERE fechaCompra IS NOT NULL
        UNION
        SELECT DISTINCT YEAR(fechaCompra) as anio FROM servidor WHERE fechaCompra IS NOT NULL
        ORDER BY anio DESC";
$res = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $anio = intval($row['anio']);
    if ($anio > 0) {
        $anios_reparacion[] = $anio;
    }
}
if (!empty($anios_reparacion) && !in_array($selected_year, $anios_reparacion, true)) {
    $selected_year = $anios_reparacion[0];
}

// 13) Tendencia de compra por precio (por mes/año)
$tendencia_compra_activos = [ 'Laptop' => [], 'PC' => [], 'Servidor' => [] ];
foreach (['Laptop','PC','Servidor'] as $tipo) {
    $tabla = strtolower($tipo);
    // CORREGIDO: Usar alias diferentes para evitar conflictos
    if ($tipo === 'Servidor') {
        $sql = "SELECT precioCompra, fechaCompra FROM servidor
                WHERE MONTH(fechaCompra) = $selected_month AND YEAR(fechaCompra) = $selected_year AND precioCompra IS NOT NULL";
    } else {
        $sql = "SELECT precioCompra, fechaCompra FROM $tabla
                WHERE MONTH(fechaCompra) = $selected_month AND YEAR(fechaCompra) = $selected_year AND precioCompra IS NOT NULL";
    }
    
    $res = sqlsrv_query($conn, $sql);
    $precios = [];
    
    if ($res) {
        while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
            $fecha_formateada = null;
            if ($row['fechaCompra']) {
                if ($row['fechaCompra'] instanceof DateTime) {
                    $fecha_formateada = $row['fechaCompra']->format('Y-m-d');
                } else {
                    $fecha_formateada = date('Y-m-d', strtotime($row['fechaCompra']));
                }
            }
            $precios[] = [ 
                'precio' => floatval($row['precioCompra']), 
                'fecha' => $fecha_formateada 
            ];
        }
    }
    
    $tendencia_compra_activos[$tipo] = $precios;
}
