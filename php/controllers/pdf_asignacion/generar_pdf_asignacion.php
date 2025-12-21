<?php
session_start();
include("../../includes/conexion.php");

// Configurar zona horaria de Lima, Perú
date_default_timezone_set('America/Lima');

// Configurar mbstring
mb_internal_encoding('UTF-8');

function generarPDFAsignacion($id_asignacion) {
    global $conn;
    
    // Obtener datos completos de la asignación
    $sql = "
    SELECT 
        asig.id_asignacion,
        asig.id_activo,
        asig.fecha_asignacion,
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
            WHEN act.tipo_activo = 'Laptop' THEN lap.id_laptop
            WHEN act.tipo_activo = 'PC' THEN pc.id_pc
            WHEN act.tipo_activo = 'Servidor' THEN srv.id_servidor
        END as id_equipo,
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
        CASE 
            WHEN act.tipo_activo = 'Laptop' THEN lap.numeroIP
            WHEN act.tipo_activo = 'PC' THEN pc.numeroIP
            WHEN act.tipo_activo = 'Servidor' THEN srv.numeroIP
        END as numero_ip,
        CASE 
            WHEN act.tipo_activo = 'Laptop' THEN lap.mac
            WHEN act.tipo_activo = 'PC' THEN pc.mac
            WHEN act.tipo_activo = 'Servidor' THEN srv.mac
        END as mac_address,
        u.username as usuario_asigno
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
    WHERE asig.id_asignacion = ?";
    
    $stmt = sqlsrv_query($conn, $sql, [$id_asignacion]);
    $datos = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if (!$datos) {
        throw new Exception("Asignación no encontrada");
    }
    
    // Formatear fecha
    $fecha_asignacion = "";
    if (isset($datos['fecha_asignacion']) && $datos['fecha_asignacion'] !== null) {
        if ($datos['fecha_asignacion'] instanceof DateTime) {
            $fecha_asignacion = $datos['fecha_asignacion']->format('d/m/Y');
        } else {
            $timestamp = strtotime($datos['fecha_asignacion']);
            if ($timestamp !== false) {
                $fecha_asignacion = date('d/m/Y', $timestamp);
            }
        }
    }
    
    // Crear estructura de carpetas mejorada
    $carpeta_base = "../../../pdf";
    $carpeta_asignacion = $carpeta_base . "/asignacion";
    $carpeta_activo = $carpeta_asignacion . "/activo_" . $datos['id_activo'] . "_" . strtolower($datos['tipo_activo']) . "_" . $datos['id_equipo'];
    $carpeta_sin_firma = $carpeta_activo . "/sinFirma";
    $carpeta_con_firma = $carpeta_activo . "/conFirma";
    
    // Crear carpetas si no existen
    if (!file_exists($carpeta_base)) {
        mkdir($carpeta_base, 0755, true);
    }
    if (!file_exists($carpeta_asignacion)) {
        mkdir($carpeta_asignacion, 0755, true);
    }
    if (!file_exists($carpeta_sin_firma)) {
        mkdir($carpeta_sin_firma, 0755, true);
    }
    if (!file_exists($carpeta_con_firma)) {
        mkdir($carpeta_con_firma, 0755, true);
    }
    
    // Generar HTML del documento
    $html = generarHTMLDocumento($datos, $fecha_asignacion);
    
    return [
        'html' => $html, 
        'datos' => $datos, 
        'carpeta_sin_firma' => $carpeta_sin_firma,
        'carpeta_con_firma' => $carpeta_con_firma,
        'fecha_asignacion' => $fecha_asignacion,
        'ruta_relativa' => "pdf/asignacion/activo_" . $datos['id_activo'] . "_" . strtolower($datos['tipo_activo']) . "_" . $datos['id_equipo']
    ];
}

function generarPDFConDompdf($id_asignacion) {
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
        // Si no encuentra dompdf, generar PDF usando HTML con instrucciones
        $resultado = generarPDFAsignacion($id_asignacion);
        
        // Crear un HTML optimizado para conversión a PDF
        $html_pdf = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Documento de Asignación</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 20px; }
                .info-section { margin-bottom: 25px; page-break-inside: avoid; }
                .info-grid { display: table; width: 100%; }
                .info-row { display: table-row; }
                .info-label { display: table-cell; width: 30%; font-weight: bold; padding: 5px 10px 5px 0; }
                .info-value { display: table-cell; padding: 5px 0; }
                .firma-section { margin-top: 50px; border: 1px solid #ddd; padding: 20px; page-break-inside: avoid; }
                .firma-box { border: 1px solid #999; height: 80px; margin: 10px 0; }
                @media print { body { margin: 0; } }
                .download-instructions { 
                    background: #f0f8ff; 
                    border: 1px solid #007bff; 
                    padding: 15px; 
                    margin: 20px 0; 
                    border-radius: 5px; 
                }
                @media print { .download-instructions { display: none; } }
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
            <div class="download-instructions">
                <h3>📥 Para descargar como PDF:</h3>
                <ol>
                    <li>Presiona <strong>Ctrl + P</strong> (o Cmd + P en Mac)</li>
                    <li>Selecciona <strong>"Guardar como PDF"</strong> como destino</li>
                    <li><strong>IMPORTANTE:</strong> En "Más configuraciones", desactiva <strong>"Encabezados y pies de página"</strong></li>
                    <li>Haz clic en <strong>"Guardar"</strong></li>
                </ol>
                <p><small>💡 Sugerencia: El nombre recomendado es "Asignacion_' . str_pad($id_asignacion, 4, '0', STR_PAD_LEFT) . '_' . date('d-m-Y') . '.pdf"</small></p>
                <p><small>⚠️ Nota: Si aún aparece la URL, asegúrate de desactivar "Encabezados y pies de página" en las opciones de impresión.</small></p>
            </div>
            ' . substr($resultado['html'], strpos($resultado['html'], '<div class="header">')) . '
        </body>
        </html>';
        
        // Crear nombre del archivo con nombre de persona
        $nombre_persona_limpio = preg_replace('/[^A-Za-z0-9_.-]/', '_', $resultado['datos']['persona_nombre']);
        $nombre_archivo = "Asignacion_" . str_pad($id_asignacion, 4, '0', STR_PAD_LEFT) . "_" . 
                         $nombre_persona_limpio . "_" . 
                         date('d-m-Y') . ".html";
        
        // Guardar archivo HTML en la carpeta correspondiente
        $ruta_html = $resultado['carpeta_sin_firma'] . "/" . $nombre_archivo;
        file_put_contents($ruta_html, $html_pdf);
        
        return [
            'pdf_output' => $html_pdf,
            'nombre_archivo' => str_replace('.html', '.pdf', $nombre_archivo),
            'ruta_archivo' => $ruta_html,
            'datos' => $resultado['datos'],
            'es_html' => true
        ];
    }
    
    // Si encuentra dompdf, usar la implementación original
    require_once $ruta_dompdf;
    Dompdf\Autoloader::register();
    
    // Generar los datos del documento
    $resultado = generarPDFAsignacion($id_asignacion);
    
    // Configurar dompdf
    $options = new Dompdf\Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', false);
    $options->set('isHtml5ParserEnabled', false);
    $options->set('debugKeepTemp', false);
    
    $dompdf = new Dompdf\Dompdf($options);
    
    // Cargar HTML y generar PDF
    $dompdf->loadHtml($resultado['html']);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Crear nombre del archivo con nombre de persona
    $nombre_persona_limpio = preg_replace('/[^A-Za-z0-9_.-]/', '_', $resultado['datos']['persona_nombre']);
    $nombre_archivo = "Asignacion_" . str_pad($id_asignacion, 4, '0', STR_PAD_LEFT) . "_" . 
                     $nombre_persona_limpio . "_" . 
                     date('d-m-Y') . ".pdf";
    
    // Guardar PDF en la carpeta correspondiente
    $ruta_pdf = $resultado['carpeta_sin_firma'] . "/" . $nombre_archivo;
    file_put_contents($ruta_pdf, $dompdf->output());
    
    return [
        'pdf_output' => $dompdf->output(),
        'nombre_archivo' => $nombre_archivo,
        'ruta_archivo' => $ruta_pdf,
        'datos' => $resultado['datos'],
        'es_html' => false
    ];
}

function generarHTMLDocumento($datos, $fecha_asignacion) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Documento de Asignación de Activo</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 15px; color: #333; line-height: 1.3; font-size: 16px; }
            .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #007bff; padding-bottom: 18px; }
            .header h1 { margin: 0; color: #007bff; font-size: 20px; }
            .header h2 { margin: 5px 0; color: #666; font-size: 20px; }
            .header p { margin: 5px 0; font-size: 14px; }
            
            .secciones-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .seccion-cell { width: 50%; vertical-align: top; padding: 10px; }
            
            .info-section { margin-bottom: 15px; }
            .info-section h3 { background-color: #f8f9fa; padding: 6px; margin: 0 0 8px 0; border-left: 4px solid #007bff; font-size: 14px; }
            .info-grid { display: table; width: 100%; }
            .info-row { display: table-row; }
            .info-label { display: table-cell; width: 40%; font-weight: bold; padding: 2px 5px 2px 0; font-size: 12px; }
            .info-value { display: table-cell; padding: 2px 0; font-size: 12px; }
            
            .firma-section { margin-top: 20px; border: 1px solid #ddd; padding: 15px; }
            .firma-section h3 { font-size: 16px; margin: 0 0 12px 0; }
            .firma-box { border: 1px solid #999; height: 60px; margin: 10px 0; }
            .footer { margin-top: 15px; text-align: center; font-size: 12px; color: #666; }
            .clausulas { margin-top: 20px; background-color: #f9f9f9; padding: 12px; border-radius: 5px; }
            .clausulas h3 { font-size: 16px; margin: 0 0 10px 0; }
            .clausulas ol { margin: 0; padding-left: 18px; font-size: 14px; }
            .clausulas li { margin-bottom: 4px; }
            table { font-size: 14px; }
            .print-btn { margin-bottom: 18px; }
            .print-btn button { padding: 6px 12px; font-size: 14px; }
            
            @media print {
                .print-btn { display: none; }
                body { margin: 12px; font-size: 15px; }
                .header h1 { font-size: 18px; }
                .header h2 { font-size: 16px; }
                .info-section h3 { font-size: 13px; padding: 4px; }
                .clausulas h3 { font-size: 15px; }
                .firma-section h3 { font-size: 15px; }
                .clausulas ol { font-size: 12px; }
                .info-label, .info-value { font-size: 11px; padding: 1px 5px 1px 0; }
                .footer { font-size: 10px; }
                .firma-box { height: 50px; margin: 8px 0; }
                .clausulas { padding: 10px; margin-top: 15px; }
                .firma-section { padding: 12px; margin-top: 15px; }
                .seccion-cell { padding: 8px; }
                
                /* Ocultar encabezados y pies de página del navegador */
                @page {
                    margin: 0.5in;
                    size: A4;
                }
                
                /* Ocultar URL y fecha que aparece automáticamente */
                html { 
                    -webkit-print-color-adjust: exact !important;
                    color-adjust: exact !important;
                }
                
                /* Estilos adicionales para una mejor impresión */
                body {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
            }
            
            /* CSS específico para ocultar elementos de impresión del navegador */
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
        
        <div class="header">
            <h1>DOCUMENTO DE ASIGNACIÓN DE ACTIVO TECNOLÓGICO</h1>
            <h2>Sistema de Gestión de Inventario TI</h2>
            <p><strong>Fecha del documento:</strong> ' . date('d/m/Y H:i:s') . '</p>
        </div>
        
        <!-- Tabla 2x2 para las secciones principales -->
        <table class="secciones-table">
            <tr>
                <!-- Celda 1: Información de la Asignación -->
                <td class="seccion-cell">
                    <div class="info-section">
                        <h3>📋 INFORMACIÓN DE LA ASIGNACIÓN</h3>
                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-label">N° de Asignación:</div>
                                <div class="info-value">' . htmlspecialchars($datos['id_asignacion']) . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Fecha de Asignación:</div>
                                <div class="info-value">' . $fecha_asignacion . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Usuario que Asigna:</div>
                                <div class="info-value">' . htmlspecialchars($datos['usuario_asigno'] ?? 'N/A') . '</div>
                            </div>
                        </div>
                    </div>
                </td>
                
                <!-- Celda 2: Información del Responsable -->
                <td class="seccion-cell">
                    <div class="info-section">
                        <h3>👤 INFORMACIÓN DEL RESPONSABLE</h3>
                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-label">Nombre Completo:</div>
                                <div class="info-value">' . htmlspecialchars($datos['persona_nombre']) . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Email:</div>
                                <div class="info-value">' . htmlspecialchars($datos['email'] ?? 'N/A') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Teléfono:</div>
                                <div class="info-value">' . htmlspecialchars($datos['telefono'] ?? 'N/A') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Área:</div>
                                <div class="info-value">' . htmlspecialchars($datos['area_nombre'] ?? 'N/A') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Empresa:</div>
                                <div class="info-value">' . htmlspecialchars($datos['empresa_nombre'] ?? 'N/A') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Localidad:</div>
                                <div class="info-value">' . htmlspecialchars($datos['localidad_nombre'] ?? 'N/A') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Tipo de Persona:</div>
                                <div class="info-value">' . htmlspecialchars($datos['nombre_tipo_persona'] ?? 'N/A') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Situación:</div>
                                <div class="info-value">' . htmlspecialchars($datos['situacion_personal'] ?? 'N/A') . '</div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <!-- Celda 3: Información del Activo -->
                <td class="seccion-cell">
                    <div class="info-section">
                        <h3>💻 INFORMACIÓN DEL ACTIVO ASIGNADO</h3>
                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-label">Tipo de Activo:</div>
                                <div class="info-value">' . htmlspecialchars($datos['tipo_activo']) . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Descripción:</div>
                                <div class="info-value">' . htmlspecialchars($datos['activo_descripcion']) . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Marca:</div>
                                <div class="info-value">' . htmlspecialchars($datos['marca'] ?? 'N/A') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Modelo:</div>
                                <div class="info-value">' . htmlspecialchars($datos['modelo'] ?? 'N/A') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Número de Serie:</div>
                                <div class="info-value">' . htmlspecialchars($datos['numero_serial'] ?? 'N/A') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Dirección IP:</div>
                                <div class="info-value">' . htmlspecialchars($datos['numero_ip'] ?? 'N/A') . '</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Dirección MAC:</div>
                                <div class="info-value">' . htmlspecialchars($datos['mac_address'] ?? 'N/A') . '</div>
                            </div>
                        </div>
                    </div>
                </td>
                
                <!-- Celda 4: Observaciones (si existen) -->
                <td class="seccion-cell">';

    if (!empty($datos['observaciones'])) {
        $html .= '
                    <div class="info-section">
                        <h3>📝 OBSERVACIONES</h3>
                        <p style="font-size: 12px; margin: 8px 0; line-height: 1.4;">' . nl2br(htmlspecialchars($datos['observaciones'])) . '</p>
                    </div>';
    } else {
        $html .= '
                    <div class="info-section">
                        <h3>📝 OBSERVACIONES</h3>
                        <p style="font-size: 12px; margin: 8px 0; color: #666; font-style: italic;">Sin observaciones adicionales</p>
                    </div>';
    }

    $html .= '
                </td>
            </tr>
        </table>

        <div class="clausulas">
            <h3>📄 TÉRMINOS Y CONDICIONES DE LA ASIGNACIÓN</h3>
            <ol>
                <li>El usuario es responsable de los daños que se ocasiona al activo asignado.</li>
                <li>Cualquier daño o pérdida del equipo debe ser reportado inmediatamente al área de TI.</li>
                <li>No está permitido hacer modificaciones de software o hardware sin previa autorización del área de Sistemas & TI.</li>
                <li>La información contenida en este equipo es de estricto uso de la empresa y no debe ser mostrada a terceros no vinculados laboralmente a la empresa.</li>
                <li>El activo debe ser utilizado únicamente para fines laborales autorizados.</li>
                <li>El usuario debe devolver el activo en las mismas condiciones al finalizar la asignación.</li>
                <li>Este documento debe ser firmado como constancia de recibido conforme.</li>
            </ol>
        </div>

        <div class="firma-section">
            <h3>✍️ FIRMAS Y ACEPTACIÓN</h3>
            <table width="100%">
                <tr>
                    <td width="45%">
                        <div style="text-align: center;">
                            <div class="firma-box"></div>
                            <strong>FIRMA DEL RESPONSABLE</strong><br>
                            <small>(' . htmlspecialchars($datos['persona_nombre']) . ')</small><br>
                            <small>Email: ' . htmlspecialchars($datos['email'] ?? 'N/A') . '</small><br>
                            <small><strong>DNI: </strong>_________________________</small>
                        </div>
                    </td>
                    <td width="10%"></td>
                    <td width="45%">
                        <div style="text-align: center;">
                            <div class="firma-box"></div>
                            <strong>FIRMA DEL ÁREA DE TI</strong><br>
                            <small>(' . htmlspecialchars($datos['usuario_asigno'] ?? 'N/A') . ')</small><br>
                            <small>Fecha: _______________</small>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Documento generado automáticamente por el Sistema de Gestión de Inventario TI - ' . date('d/m/Y H:i:s') . ' | 
            ID Asignación: ' . htmlspecialchars($datos['id_asignacion']) . ' | ID Activo: ' . htmlspecialchars($datos['id_activo']) . '</p>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Procesar solicitud
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['id_asignacion'])) {
    try {
        $id_asignacion = $_GET['id_asignacion'];
        $accion = $_GET['accion'] ?? 'generar';
        
        if ($accion === 'descargar_pdf') {
            // Generar y descargar PDF (o HTML si dompdf no está disponible)
            $pdf_result = generarPDFConDompdf($id_asignacion);
            
            if ($pdf_result['es_html']) {
                // Si es HTML, mostrar página con instrucciones
                header('Content-Type: text/html; charset=utf-8');
                echo $pdf_result['pdf_output'];
                exit;
            } else {
                // Si es PDF real, configurar headers para descarga
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $pdf_result['nombre_archivo'] . '"');
                header('Content-Length: ' . strlen($pdf_result['pdf_output']));
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                
                echo $pdf_result['pdf_output'];
                exit;
            }
            
        } elseif ($accion === 'ver_modal') {
            $resultado = generarPDFAsignacion($id_asignacion);
            
            // Crear nombre del archivo con nombre de persona
            $nombre_persona_limpio = preg_replace('/[^A-Za-z0-9_.-]/', '_', $resultado['datos']['persona_nombre']);
            $nombre_archivo = "asignacion_" . $id_asignacion . "_" . $nombre_persona_limpio . "_" . date('Y-m-d') . "_sinFirma.html";
            $ruta_archivo = $resultado['carpeta_sin_firma'] . "/" . $nombre_archivo;
            
            if (!file_exists($ruta_archivo)) {
                file_put_contents($ruta_archivo, $resultado['html']);
            }
            
            // Devolver JSON con la información del documento
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'html' => $resultado['html'],
                'archivo_existe' => file_exists($ruta_archivo),
                'ruta_archivo' => $resultado['ruta_relativa'] . "/sinFirma/" . $nombre_archivo
            ]);
            exit;
            
        } else {
            // Comportamiento original - guardar y mostrar HTML
            $resultado = generarPDFAsignacion($id_asignacion);
            
            // Crear nombre del archivo con nombre de persona
            $nombre_persona_limpio = preg_replace('/[^A-Za-z0-9_.-]/', '_', $resultado['datos']['persona_nombre']);
            $nombre_archivo = "asignacion_" . $id_asignacion . "_" . $nombre_persona_limpio . "_" . date('Y-m-d') . "_sinFirma.html";
            $ruta_archivo = $resultado['carpeta_sin_firma'] . "/" . $nombre_archivo;
            
            file_put_contents($ruta_archivo, $resultado['html']);
            
            header('Content-Type: text/html; charset=utf-8');
            echo $resultado['html'];
        }
        
    } catch (Exception $e) {
        if (isset($_GET['accion']) && $_GET['accion'] === 'ver_modal') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } else {
            echo '<h2>Error al generar documento:</h2>';
            echo '<p>' . $e->getMessage() . '</p>';
        }
    }
} else {
    header('Location: ../../views/crud_asignacion.php');
    exit;
}
?>