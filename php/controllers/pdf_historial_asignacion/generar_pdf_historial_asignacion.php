<?php
session_start();
include("../../includes/conexion.php");

// Configurar zona horaria de Lima, Perú
date_default_timezone_set('America/Lima');

// Configurar mbstring
mb_internal_encoding('UTF-8');

function generarPDFHistorial($filtros, $tipo_reporte = 'completo', $opciones = []) {
    global $conn;
    
    // Construir consulta SQL con filtros
    $sql = "
    SELECT 
        asig.id_asignacion,
        asig.id_activo,
        asig.fecha_asignacion,
        asig.fecha_retorno,
        asig.observaciones,
        CONCAT(p.nombre, ' ', p.apellido) as persona_nombre,
        p.correo as email,
        p.celular as telefono,
        l.localidad_nombre,
        a.nombre as area_nombre,
        e.nombre as empresa_nombre,
        sp.situacion as situacion_personal,
        tp.nombre_tipo_persona,
        CASE 
            WHEN act.tipo_activo = 'Laptop' THEN CONCAT('Laptop - ', lap.nombreEquipo, ' (', lap.modelo, ')')
            WHEN act.tipo_activo = 'PC' THEN CONCAT('PC - ', pc.nombreEquipo, ' (', pc.modelo, ')')
            WHEN act.tipo_activo = 'Servidor' THEN CONCAT('Servidor - ', srv.nombreEquipo, ' (', srv.modelo, ')')
        END as activo_descripcion,
        act.tipo_activo,
        CASE 
            WHEN act.tipo_activo = 'Laptop' THEN lap.numeroSerial
            WHEN act.tipo_activo = 'PC' THEN pc.numeroSerial
            WHEN act.tipo_activo = 'Servidor' THEN srv.numeroSerial
        END as numero_serial,
        CASE 
            WHEN act.tipo_activo = 'Laptop' THEN m_lap.nombre
            WHEN act.tipo_activo = 'PC' THEN m_pc.nombre
            WHEN act.tipo_activo = 'Servidor' THEN m_srv.nombre
        END as marca,
        CASE 
            WHEN act.tipo_activo = 'Laptop' THEN lap.modelo
            WHEN act.tipo_activo = 'PC' THEN pc.modelo
            WHEN act.tipo_activo = 'Servidor' THEN srv.modelo
        END as modelo,
        u.username as usuario_asigno,
        CASE 
            WHEN asig.fecha_retorno IS NULL THEN 'Activo'
            ELSE 'Retornado'
        END as estado_asignacion,
        CASE 
            WHEN asig.fecha_retorno IS NOT NULL THEN 
                DATEDIFF(day, asig.fecha_asignacion, asig.fecha_retorno)
            ELSE 
                DATEDIFF(day, asig.fecha_asignacion, GETDATE())
        END as duracion_dias
    FROM asignacion asig
    INNER JOIN persona p ON asig.id_persona = p.id_persona
    LEFT JOIN localidad l ON p.id_localidad = l.id_localidad
    LEFT JOIN area a ON p.id_area = a.id_area
    LEFT JOIN empresa e ON p.id_empresa = e.id_empresa
    LEFT JOIN situacion_personal sp ON p.id_situacion_personal = sp.id_situacion
    LEFT JOIN tipo_persona tp ON p.id_tipo_persona = tp.id_tipo_persona
    INNER JOIN activo act ON asig.id_activo = act.id_activo
    LEFT JOIN laptop lap ON act.id_laptop = lap.id_laptop
    LEFT JOIN pc pc ON act.id_pc = pc.id_pc
    LEFT JOIN servidor srv ON act.id_servidor = srv.id_servidor
    LEFT JOIN marca m_lap ON lap.id_marca = m_lap.id_marca
    LEFT JOIN marca m_pc ON pc.id_marca = m_pc.id_marca
    LEFT JOIN marca m_srv ON srv.id_marca = m_srv.id_marca
    LEFT JOIN usuario u ON asig.id_usuario = u.id_usuario
    WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filtros['numero_serial'])) {
        $sql .= " AND (lap.numeroSerial LIKE ? OR pc.numeroSerial LIKE ? OR srv.numeroSerial LIKE ?)";
        $like_serial = '%' . $filtros['numero_serial'] . '%';
        $params[] = $like_serial;
        $params[] = $like_serial;
        $params[] = $like_serial;
    }
    
    if (!empty($filtros['nombre_activo'])) {
        $sql .= " AND (lap.nombreEquipo LIKE ? OR pc.nombreEquipo LIKE ? OR srv.nombreEquipo LIKE ?)";
        $like_nombre = '%' . $filtros['nombre_activo'] . '%';
        $params[] = $like_nombre;
        $params[] = $like_nombre;
        $params[] = $like_nombre;
    }
    
    if (!empty($filtros['persona'])) {
        $sql .= " AND CONCAT(p.nombre, ' ', p.apellido) LIKE ?";
        $params[] = '%' . $filtros['persona'] . '%';
    }
    
    if (!empty($filtros['tipo_activo'])) {
        $sql .= " AND act.tipo_activo = ?";
        $params[] = $filtros['tipo_activo'];
    }
    
    if (!empty($filtros['estado_asignacion'])) {
        if ($filtros['estado_asignacion'] === 'Activo') {
            $sql .= " AND asig.fecha_retorno IS NULL";
        } else if ($filtros['estado_asignacion'] === 'Retornado') {
            $sql .= " AND asig.fecha_retorno IS NOT NULL";
        }
    }
    
    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND asig.fecha_asignacion >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    
    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND asig.fecha_asignacion <= ?";
        $params[] = $filtros['fecha_hasta'];
    }
    
    $sql .= " ORDER BY asig.fecha_asignacion DESC";
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    $datos = [];
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Formatear fechas
        if (isset($row['fecha_asignacion']) && $row['fecha_asignacion'] instanceof DateTime) {
            $row['fecha_asignacion_formatted'] = $row['fecha_asignacion']->format('d/m/Y');
        }
        if (isset($row['fecha_retorno']) && $row['fecha_retorno'] instanceof DateTime) {
            $row['fecha_retorno_formatted'] = $row['fecha_retorno']->format('d/m/Y');
        } else {
            $row['fecha_retorno_formatted'] = '-';
        }
        $datos[] = $row;
    }
    
    // Calcular estadísticas
    $estadisticas = calcularEstadisticas($datos);
    
    // Crear estructura de carpetas
    $carpeta_base = "../../../pdf";
    $carpeta_historial = $carpeta_base . "/historial_asignaciones";
    $carpeta_reportes = $carpeta_historial . "/reportes_" . date('Y-m');
    
    if (!file_exists($carpeta_base)) {
        mkdir($carpeta_base, 0755, true);
    }
    if (!file_exists($carpeta_historial)) {
        mkdir($carpeta_historial, 0755, true);
    }
    if (!file_exists($carpeta_reportes)) {
        mkdir($carpeta_reportes, 0755, true);
    }
    
    // Generar HTML del documento
    $html = generarHTMLReporte($datos, $estadisticas, $filtros, $tipo_reporte, $opciones);
    
    return [
        'html' => $html,
        'datos' => $datos,
        'estadisticas' => $estadisticas,
        'carpeta_reportes' => $carpeta_reportes,
        'filtros_aplicados' => $filtros,
        'tipo_reporte' => $tipo_reporte,
        'opciones' => $opciones
    ];
}

function calcularEstadisticas($datos) {
    $total = count($datos);
    $activas = 0;
    $retornadas = 0;
    $suma_dias = 0;
    $count_dias = 0;
    
    foreach ($datos as $item) {
        if ($item['estado_asignacion'] === 'Activo') {
            $activas++;
        } else {
            $retornadas++;
        }
        
        if (isset($item['duracion_dias']) && is_numeric($item['duracion_dias'])) {
            $suma_dias += $item['duracion_dias'];
            $count_dias++;
        }
    }
    
    $promedio_dias = $count_dias > 0 ? round($suma_dias / $count_dias, 1) : 0;
    
    return [
        'total' => $total,
        'activas' => $activas,
        'retornadas' => $retornadas,
        'promedio_dias' => $promedio_dias
    ];
}

function generarPDFConDompdf($filtros, $tipo_reporte = 'completo', $opciones = []) {
    // Verificar múltiples ubicaciones posibles de dompdf
    $posibles_rutas = [
        '../../../php/libs/dompdf-3.1.2/src/Autoloader.php',
        '../../../libs/dompdf-3.1.2/src/Autoloader.php',
        '../../../vendor/dompdf/dompdf/src/Autoloader.php',
        '../../../dompdf/src/Autoloader.php'
    ];
    
    $ruta_dompdf = null;
    foreach ($posibles_rutas as $ruta) {
        if (file_exists($ruta)) {
            $ruta_dompdf = $ruta;
            break;
        }
    }
    
    if (!$ruta_dompdf) {
        // Si no encuentra dompdf, generar HTML con botón de imprimir
        $resultado = generarPDFHistorial($filtros, $tipo_reporte, $opciones);
        
        $html_pdf = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Historial de Asignaciones</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .print-btn { margin-bottom: 18px; }
                .print-btn button { padding: 6px 12px; font-size: 14px; }
                @media print { 
                    .print-btn { display: none; }
                }
                @page { 
                    margin: 0.5in; 
                    size: A4;
                    @top-left { content: ""; }
                    @top-center { content: ""; }
                    @top-right { content: ""; }
                    @bottom-left { content: ""; }
                    @bottom-center { content: ""; }
                    @bottom-right { content: ""; }
                }
            </style>
        </head>
        <body>
            <div class="print-btn" style="margin-bottom: 15px;">
                <button onclick="window.print()">🖨️ Imprimir</button>
            </div>
            
            ' . $resultado['html'] . '
        </body>
        </html>';
        
        $nombre_archivo = "Historial_Asignaciones_" . $tipo_reporte . "_" . date('d-m-Y') . ".html";
        $ruta_html = $resultado['carpeta_reportes'] . "/" . $nombre_archivo;
        file_put_contents($ruta_html, $html_pdf);
        
        return [
            'pdf_output' => $html_pdf,
            'nombre_archivo' => str_replace('.html', '.pdf', $nombre_archivo),
            'ruta_archivo' => $ruta_html,
            'datos' => $resultado['datos'],
            'estadisticas' => $resultado['estadisticas'],
            'es_html' => true
        ];
    }
    
    // Si encuentra dompdf, usar la implementación
    require_once $ruta_dompdf;
    Dompdf\Autoloader::register();
    
    $resultado = generarPDFHistorial($filtros, $tipo_reporte, $opciones);
    
    // Configurar dompdf
    $options = new Dompdf\Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', false);
    $options->set('isHtml5ParserEnabled', false);
    $options->set('debugKeepTemp', false);
    
    $dompdf = new Dompdf\Dompdf($options);
    
    $dompdf->loadHtml($resultado['html']);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $nombre_archivo = "Historial_Asignaciones_" . $tipo_reporte . "_" . date('d-m-Y') . ".pdf";
    $ruta_pdf = $resultado['carpeta_reportes'] . "/" . $nombre_archivo;
    file_put_contents($ruta_pdf, $dompdf->output());
    
    return [
        'pdf_output' => $dompdf->output(),
        'nombre_archivo' => $nombre_archivo,
        'ruta_archivo' => $ruta_pdf,
        'datos' => $resultado['datos'],
        'estadisticas' => $resultado['estadisticas'],
        'es_html' => false
    ];
}

function generarHTMLReporte($datos, $estadisticas, $filtros, $tipo_reporte, $opciones) {
    $incluir_filtros = isset($opciones['incluir_filtros']) ? $opciones['incluir_filtros'] : true;
    $incluir_fecha = isset($opciones['incluir_fecha_generacion']) ? $opciones['incluir_fecha_generacion'] : true;
    $incluir_usuario = isset($opciones['incluir_usuario']) ? $opciones['incluir_usuario'] : true;
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Historial de Asignaciones</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 15px; color: #333; line-height: 1.3; font-size: 14px; }
            .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #007bff; padding-bottom: 18px; }
            .header h1 { margin: 0; color: #007bff; font-size: 18px; }
            .header h2 { margin: 5px 0; color: #666; font-size: 16px; }
            .info-section { margin-bottom: 20px; }
            .info-section h3 { background-color: #f8f9fa; padding: 8px; margin: 0 0 10px 0; border-left: 4px solid #007bff; font-size: 14px; }
            .estadisticas-grid { display: table; width: 100%; margin-bottom: 20px; }
            .estadistica-item { display: table-cell; text-align: center; padding: 10px; border: 1px solid #ddd; }
            .estadistica-numero { font-size: 24px; font-weight: bold; color: #007bff; }
            .estadistica-label { font-size: 12px; color: #666; }
            .filtros-aplicados { background-color: #f9f9f9; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
            .filtros-aplicados h4 { margin: 0 0 10px 0; font-size: 14px; }
            .filtro-item { display: inline-block; margin: 5px 10px 5px 0; font-size: 12px; }
            .tabla-datos { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11px; }
            .tabla-datos th { background-color: #007bff; color: white; padding: 8px 4px; text-align: left; font-size: 10px; }
            .tabla-datos td { padding: 6px 4px; border-bottom: 1px solid #ddd; font-size: 10px; }
            .tabla-datos tr:nth-child(even) { background-color: #f9f9f9; }
            .estado-activo { color: #28a745; font-weight: bold; }
            .estado-retornado { color: #6c757d; }
            .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
            
            /* Estilos para reporte detallado */
            .detalle-asignacion { border: 1px solid #ddd; margin-bottom: 20px; padding: 15px; page-break-inside: avoid; }
            .detalle-header { background-color: #f8f9fa; padding: 10px; margin: -15px -15px 15px -15px; border-bottom: 1px solid #ddd; }
            .detalle-grid { display: table; width: 100%; }
            .detalle-seccion { margin-bottom: 15px; }
            .detalle-seccion h4 { color: #007bff; font-size: 14px; margin: 0 0 8px 0; border-bottom: 1px solid #eee; padding-bottom: 4px; }
            .detalle-item { display: table-row; }
            .detalle-label { display: table-cell; width: 30%; font-weight: bold; padding: 3px 10px 3px 0; font-size: 11px; }
            .detalle-valor { display: table-cell; padding: 3px 0; font-size: 11px; }
            
            @media print {
                body { margin: 12px; font-size: 15px; }
                .header h1 { font-size: 18px; }
                .header h2 { font-size: 16px; }
                .info-section h3 { font-size: 13px; padding: 4px; }
                .tabla-datos { font-size: 9px; }
                .tabla-datos th { font-size: 8px; padding: 6px 3px; }
                .tabla-datos td { font-size: 9px; padding: 4px 3px; }
                .estadistica-numero { font-size: 20px; }
                .detalle-asignacion { margin-bottom: 15px; padding: 10px; }
                .detalle-label, .detalle-valor { font-size: 10px; }
                @page { 
                    margin: 0.5in; 
                    size: A4;
                    @top-left { content: ""; }
                    @top-center { content: ""; }
                    @top-right { content: ""; }
                    @bottom-left { content: ""; }
                    @bottom-center { content: ""; }
                    @bottom-right { content: ""; }
                }
            }
        </style>
    </head>
    <body>        
        <div class="header">
            <h1>REPORTE DE HISTORIAL DE ASIGNACIONES</h1>
            <h2>Sistema de Gestión de Inventario TI</h2>';
    
    if ($incluir_fecha) {
        $html .= '<p><strong>Fecha de generación:</strong> ' . date('d/m/Y H:i:s') . '</p>';
    }
    
    if ($incluir_usuario && isset($_SESSION['username'])) {
        $html .= '<p><strong>Generado por:</strong> ' . htmlspecialchars($_SESSION['username']) . '</p>';
    }
    
    $html .= '<p><strong>Tipo de reporte:</strong> ' . ucfirst($tipo_reporte) . '</p>
        </div>';
    
    // Mostrar estadísticas generales
    $html .= '
    <div class="info-section">
        <h3>📊 ESTADÍSTICAS GENERALES</h3>
        <div class="estadisticas-grid">
            <div class="estadistica-item">
                <div class="estadistica-numero">' . $estadisticas['total'] . '</div>
                <div class="estadistica-label">Total Asignaciones</div>
            </div>
            <div class="estadistica-item">
                <div class="estadistica-numero">' . $estadisticas['activas'] . '</div>
                <div class="estadistica-label">Asignaciones Activas</div>
            </div>
            <div class="estadistica-item">
                <div class="estadistica-numero">' . $estadisticas['retornadas'] . '</div>
                <div class="estadistica-label">Asignaciones Retornadas</div>
            </div>
            <div class="estadistica-item">
                <div class="estadistica-numero">' . $estadisticas['promedio_dias'] . '</div>
                <div class="estadistica-label">Promedio Días</div>
            </div>
        </div>
    </div>';
    
    // Mostrar filtros aplicados
    if ($incluir_filtros && !empty(array_filter($filtros))) {
        $html .= '
        <div class="filtros-aplicados">
            <h4>🔍 Filtros Aplicados:</h4>';
        
        foreach ($filtros as $key => $value) {
            if (!empty($value)) {
                $label = [
                    'numero_serial' => 'Número Serial',
                    'nombre_activo' => 'Nombre Activo',
                    'persona' => 'Persona',
                    'tipo_activo' => 'Tipo Activo',
                    'estado_asignacion' => 'Estado',
                    'fecha_desde' => 'Fecha Desde',
                    'fecha_hasta' => 'Fecha Hasta'
                ][$key] ?? $key;
                
                $html .= '<span class="filtro-item"><strong>' . $label . ':</strong> ' . htmlspecialchars($value) . '</span>';
            }
        }
        
        $html .= '</div>';
    }
    
    // Generar contenido según el tipo de reporte
    if ($tipo_reporte === 'detallado') {
        // Reporte detallado - mostrar información completa de cada asignación
        $html .= '
        <div class="info-section">
            <h3>📋 DETALLE COMPLETO DE ASIGNACIONES (' . count($datos) . ' registros)</h3>';
        
        foreach ($datos as $item) {
            $clase_estado = $item['estado_asignacion'] === 'Activo' ? 'estado-activo' : 'estado-retornado';
            
            $html .= '
            <div class="detalle-asignacion">
                <div class="detalle-header">
                    <h3 style="margin: 0; color: #007bff;">Asignación #' . htmlspecialchars($item['id_asignacion']) . ' - ' . htmlspecialchars($item['activo_descripcion']) . '</h3>
                </div>
                
                <div class="detalle-grid">
                    <div style="display: table-row;">
                        <!-- Columna 1: Información de la Persona -->
                        <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 15px;">
                            <div class="detalle-seccion">
                                <h4>👤 Información de la Persona</h4>
                                <div class="detalle-item">
                                    <div class="detalle-label">Nombre:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['persona_nombre']) . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Email:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['email'] ?? 'No especificado') . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Teléfono:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['telefono'] ?? 'No especificado') . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Área:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['area_nombre'] ?? 'No especificado') . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Empresa:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['empresa_nombre'] ?? 'No especificado') . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Localidad:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['localidad_nombre'] ?? 'No especificado') . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Tipo Persona:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['nombre_tipo_persona'] ?? 'No especificado') . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Situación:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['situacion_personal'] ?? 'No especificado') . '</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna 2: Información del Activo y Asignación -->
                        <div style="display: table-cell; width: 50%; vertical-align: top;">
                            <div class="detalle-seccion">
                                <h4>💻 Información del Activo</h4>
                                <div class="detalle-item">
                                    <div class="detalle-label">Tipo:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['tipo_activo']) . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Descripción:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['activo_descripcion']) . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">N° Serial:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['numero_serial'] ?? 'No especificado') . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Marca:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['marca'] ?? 'No especificado') . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Modelo:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['modelo'] ?? 'No especificado') . '</div>
                                </div>
                            </div>
                            
                            <div class="detalle-seccion">
                                <h4>📅 Información de la Asignación</h4>
                                <div class="detalle-item">
                                    <div class="detalle-label">Fecha Asignación:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['fecha_asignacion_formatted'] ?? 'N/A') . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Fecha Retorno:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['fecha_retorno_formatted']) . '</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Duración:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['duracion_dias'] ?? '0') . ' días</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Estado:</div>
                                    <div class="detalle-valor"><span class="' . $clase_estado . '">' . htmlspecialchars($item['estado_asignacion']) . '</span></div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Usuario Asignó:</div>
                                    <div class="detalle-valor">' . htmlspecialchars($item['usuario_asigno'] ?? 'No especificado') . '</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
            
            // Agregar observaciones si existen
            if (!empty($item['observaciones'])) {
                $html .= '
                <div class="detalle-seccion" style="margin-top: 10px;">
                    <h4>📝 Observaciones</h4>
                    <p style="margin: 5px 0; font-size: 11px; line-height: 1.4;">' . nl2br(htmlspecialchars($item['observaciones'])) . '</p>
                </div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
    } else {
        // Reporte completo (tabla resumen)
        $html .= '
        <div class="info-section">
            <h3>📋 RESUMEN DE ASIGNACIONES (' . count($datos) . ' registros)</h3>
            <table class="tabla-datos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Activo</th>
                        <th>N° Serial</th>
                        <th>Marca/Modelo</th>
                        <th>Persona Asignada</th>
                        <th>Área/Empresa</th>
                        <th>Fecha Asignación</th>
                        <th>Fecha Retorno</th>
                        <th>Duración</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($datos as $item) {
            $clase_estado = $item['estado_asignacion'] === 'Activo' ? 'estado-activo' : 'estado-retornado';
            
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item['id_asignacion']) . '</td>
                        <td>' . htmlspecialchars($item['activo_descripcion']) . '</td>
                        <td>' . htmlspecialchars($item['numero_serial'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars(($item['marca'] ?? 'N/A') . ' / ' . ($item['modelo'] ?? 'N/A')) . '</td>
                        <td>' . htmlspecialchars($item['persona_nombre']) . '</td>
                        <td>' . htmlspecialchars(($item['area_nombre'] ?? 'N/A') . ' / ' . ($item['empresa_nombre'] ?? 'N/A')) . '</td>
                        <td>' . htmlspecialchars($item['fecha_asignacion_formatted'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($item['fecha_retorno_formatted']) . '</td>
                        <td>' . htmlspecialchars($item['duracion_dias'] ?? '0') . ' días</td>
                        <td><span class="' . $clase_estado . '">' . htmlspecialchars($item['estado_asignacion']) . '</span></td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>';
    }
    
    $html .= '
        <div class="footer">
            <p>Documento generado automáticamente por el Sistema de Gestión de Inventario TI - ' . date('d/m/Y H:i:s') . ' | 
            Total de registros: ' . count($datos) . ' | Tipo: ' . ucfirst($tipo_reporte) . '</p>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Procesar solicitud
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $filtros = [
            'numero_serial' => $_POST['numero_serial'] ?? '',
            'nombre_activo' => $_POST['nombre_activo'] ?? '',
            'persona' => $_POST['persona'] ?? '',
            'tipo_activo' => $_POST['tipo_activo'] ?? '',
            'estado_asignacion' => $_POST['estado_asignacion'] ?? '',
            'fecha_desde' => $_POST['fecha_desde'] ?? '',
            'fecha_hasta' => $_POST['fecha_hasta'] ?? ''
        ];
        
        $tipo_reporte = $_POST['tipo_reporte'] ?? 'completo';
        $opciones = [
            'incluir_filtros' => isset($_POST['incluir_filtros']),
            'incluir_fecha_generacion' => isset($_POST['incluir_fecha_generacion']),
            'incluir_usuario' => isset($_POST['incluir_usuario'])
        ];
        
        $pdf_result = generarPDFConDompdf($filtros, $tipo_reporte, $opciones);
        
        if ($pdf_result['es_html']) {
            header('Content-Type: text/html; charset=utf-8');
            echo $pdf_result['pdf_output'];
            exit;
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $pdf_result['nombre_archivo'] . '"');
            header('Content-Length: ' . strlen($pdf_result['pdf_output']));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            echo $pdf_result['pdf_output'];
            exit;
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    header('Location: ../../views/crud_historial_asignacion.php');
    exit;
}
?>
