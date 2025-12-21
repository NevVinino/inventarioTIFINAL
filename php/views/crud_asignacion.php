<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Recuperar datos de sesión
$id_usuario_sesion = $_SESSION['id_usuario'] ?? '';
$nombre_usuario_sesion = $_SESSION['username'] ?? '';

// Consultas para selects - Removed cedula field since it doesn't exist
$personas = sqlsrv_query($conn, "SELECT id_persona, CONCAT(nombre, ' ', apellido) as nombre_completo FROM persona ORDER BY nombre, apellido");

// Consulta para activos disponibles (no asignados actualmente) + todos los activos para edición
$sql_activos = "
SELECT DISTINCT
    a.id_activo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN CONCAT('Laptop - ', l.nombreEquipo, ' (', l.modelo, ')')
        WHEN a.tipo_activo = 'PC' THEN CONCAT('PC - ', p.nombreEquipo, ' (', p.modelo, ')')
    END as descripcion_activo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN ea_l.vestado_activo
        WHEN a.tipo_activo = 'PC' THEN ea_p.vestado_activo
    END as estado,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM asignacion asig 
            WHERE asig.id_activo = a.id_activo 
            AND asig.fecha_retorno IS NULL
        ) THEN 'Asignado'
        ELSE 'Disponible'
    END as estado_asignacion
FROM activo a
LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
LEFT JOIN pc p ON a.id_pc = p.id_pc
LEFT JOIN estado_activo ea_l ON l.id_estado_activo = ea_l.id_estado_activo
LEFT JOIN estado_activo ea_p ON p.id_estado_activo = ea_p.id_estado_activo
WHERE (
    (a.tipo_activo = 'Laptop' AND ea_l.vestado_activo IN ('Disponible', 'Asignado')) OR
    (a.tipo_activo = 'PC' AND ea_p.vestado_activo IN ('Disponible', 'Asignado'))
)
ORDER BY descripcion_activo";

$activos_disponibles = sqlsrv_query($conn, $sql_activos);

// Consulta principal para asignaciones - Add id_persona and improve date handling
$sql_asignaciones = "
SELECT 
    asig.id_asignacion,
    asig.id_activo,
    asig.id_persona,
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
    CONCAT(jefe.nombre, ' ', jefe.apellido) as jefe_inmediato,
    CASE 
        WHEN act.tipo_activo = 'Laptop' THEN CONCAT('Laptop - ', lap.nombreEquipo, ' (', lap.modelo, ')')
        WHEN act.tipo_activo = 'PC' THEN CONCAT('PC - ', pc.nombreEquipo, ' (', pc.modelo, ')')
    END as activo_descripcion,
    act.tipo_activo,
    CASE 
        WHEN act.tipo_activo = 'Laptop' THEN lap.numeroSerial
        WHEN act.tipo_activo = 'PC' THEN pc.numeroSerial
    END as numero_serial,
    CASE 
        WHEN act.tipo_activo = 'Laptop' THEN lap.numeroIP
        WHEN act.tipo_activo = 'PC' THEN pc.numeroIP
    END as numero_ip,
    CASE 
        WHEN act.tipo_activo = 'Laptop' THEN lap.mac
        WHEN act.tipo_activo = 'PC' THEN pc.mac
    END as mac_address,
    u.username as usuario_asigno
FROM asignacion asig
INNER JOIN persona p ON asig.id_persona = p.id_persona
LEFT JOIN localidad l ON p.id_localidad = l.id_localidad
LEFT JOIN area a ON p.id_area = a.id_area
LEFT JOIN empresa e ON p.id_empresa = e.id_empresa
LEFT JOIN situacion_personal sp ON p.id_situacion_personal = sp.id_situacion
LEFT JOIN tipo_persona tp ON p.id_tipo_persona = tp.id_tipo_persona
LEFT JOIN persona jefe ON p.jefe_inmediato = jefe.id_persona
INNER JOIN activo act ON asig.id_activo = act.id_activo
LEFT JOIN laptop lap ON act.id_laptop = lap.id_laptop
LEFT JOIN pc pc ON act.id_pc = pc.id_pc
LEFT JOIN usuario u ON asig.id_usuario = u.id_usuario
ORDER BY asig.fecha_asignacion DESC";

$asignaciones = sqlsrv_query($conn, $sql_asignaciones);
if ($asignaciones === false) {
    die("Error en consulta de asignaciones: " . print_r(sqlsrv_errors(), true));
}

$filas_asignaciones = [];
while ($fila = sqlsrv_fetch_array($asignaciones, SQLSRV_FETCH_ASSOC)) {
    $filas_asignaciones[] = $fila;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Asignaciones</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <style>
        /* Estilos para la sección de documentos */
        .seccion-documentos {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .seccion-documentos h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
        }
        
        .documentos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .documento-item {
            padding: 15px;
            background-color: white;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .documento-item strong {
            display: block;
            margin-bottom: 10px;
            color: #343a40;
        }
        
        .documento-acciones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-generar-pdf,
        .btn-subir-firma,
        .btn-ver-pdf {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-generar-pdf:hover,
        .btn-subir-firma:hover,
        .btn-ver-pdf:hover {
            background-color: #0056b3;
        }
        
        .btn-subir-firma {
            background-color: #28a745;
        }
        
        .btn-subir-firma:hover {
            background-color: #1e7e34;
        }
        
        .btn-ver-pdf {
            background-color: #6c757d;
        }
        
        .btn-ver-pdf:hover {
            background-color: #545b62;
        }
        
        /* Estilos para el modal del visor de documentos */
        .modal-visor {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .modal-visor-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: none;
            width: 95%;
            height: 90%;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }
        
        .modal-visor-header {
            background-color: #007bff;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }
        
        .modal-visor-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .modal-visor-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }
        
        .modal-visor-close:hover {
            background-color: rgba(255,255,255,0.2);
        }
        
        .modal-visor-body {
            flex: 1;
            padding: 0;
            overflow: hidden;
        }
        
        .documento-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .btn-ver-documento {
            background-color: #17a2b8;
        }
        
        .btn-ver-documento:hover {
            background-color: #138496;
        }
    </style>
</head>
<body>

    <!-- Incluir Header -->
    <?php include('includes/header.php'); ?>

    <!-- Incluir Sidebar -->
    <?php include('includes/sidebar.php'); ?>

    <main class="main-content" id="mainContent">

        <a href="vista_admin.php" class="back-button">
            <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
        </a>

        <div class="main-container">
            <div class="top-bar">
                <h2>Gestión de Asignaciones</h2>
                <input type="text" id="buscador" placeholder="Buscar asignación">
                <button id="btnNuevo">+ NUEVA ASIGNACIÓN</button>
            </div>

            <!-- Tabla de asignaciones -->
            <table id="tablaAsignaciones">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Persona Asignada</th>
                        <th>Email</th>
                        <th>Activo</th>
                        <th>Tipo</th>
                        <th>Fecha Asignación</th>
                        <th>Fecha Retorno</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    
                    if (count($filas_asignaciones) === 0) {
                        echo "<tr><td colspan='9' style='text-align:center;'>No se encontraron asignaciones registradas</td></tr>";
                    }
                    
                    foreach ($filas_asignaciones as $a) { 
                        $fecha_asignacion = "";
                        $fecha_retorno = "";
                        $fecha_asignacion_input = "";
                        $fecha_retorno_display = "";
                        $estado_asignacion = "";
                        
                        // Mejor manejo de fechas - Debug para verificar valores
                        if (isset($a['fecha_asignacion']) && $a['fecha_asignacion'] !== null) {
                            if ($a['fecha_asignacion'] instanceof DateTime) {
                                $fecha_asignacion = $a['fecha_asignacion']->format('d/m/Y');
                                $fecha_asignacion_input = $a['fecha_asignacion']->format('Y-m-d');
                            } else {
                                // Convertir string a fecha
                                $timestamp = strtotime($a['fecha_asignacion']);
                                if ($timestamp !== false) {
                                    $fecha_asignacion = date('d/m/Y', $timestamp);
                                    $fecha_asignacion_input = date('Y-m-d', $timestamp);
                                }
                            }
                        }
                        
                        if (isset($a['fecha_retorno']) && $a['fecha_retorno'] !== null) {
                            if ($a['fecha_retorno'] instanceof DateTime) {
                                $fecha_retorno = $a['fecha_retorno']->format('d/m/Y');
                                $fecha_retorno_display = $a['fecha_retorno']->format('d/m/Y');
                            } else {
                                // Convertir string a fecha
                                $timestamp = strtotime($a['fecha_retorno']);
                                if ($timestamp !== false) {
                                    $fecha_retorno = date('d/m/Y', $timestamp);
                                    $fecha_retorno_display = date('d/m/Y', $timestamp);
                                }
                            }
                            $estado_asignacion = "Retornado";
                        } else {
                            $fecha_retorno_display = "Pendiente";
                            $estado_asignacion = "Activo";
                        }
                        
                        $clase_estado = $estado_asignacion === "Activo" ? "estado-asignado" : "estado-disponible";
                    ?>
                    <tr class="<?= $clase_estado ?>">
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($a['persona_nombre'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['activo_descripcion'] ?? '') ?></td>
                        <td><?= htmlspecialchars($a['tipo_activo'] ?? '') ?></td>
                        <td><?= $fecha_asignacion ?></td>
                        <td><?= $fecha_retorno ?: 'Pendiente' ?></td>
                        <td class="estado-celda"><?= $estado_asignacion ?></td>
                        <td>
                            <div class="acciones">
                                <!-- Botón ver con atributos data corregidos -->
                                <button type="button" class="btn-icon btn-ver" 
                                    data-id="<?= htmlspecialchars($a['id_asignacion']) ?>"
                                    data-persona="<?= htmlspecialchars($a['persona_nombre']) ?>"
                                    data-email="<?= htmlspecialchars($a['email'] ?? 'Sin email') ?>"
                                    data-telefono="<?= htmlspecialchars($a['telefono'] ?? 'Sin teléfono') ?>"
                                    data-localidad="<?= htmlspecialchars($a['localidad_nombre'] ?? 'Sin localidad') ?>"
                                    data-area="<?= htmlspecialchars($a['area_nombre'] ?? 'Sin área') ?>"
                                    data-empresa="<?= htmlspecialchars($a['empresa_nombre'] ?? 'Sin empresa') ?>"
                                    data-situacion="<?= htmlspecialchars($a['situacion_personal'] ?? 'Sin situación') ?>"
                                    data-tipo-persona="<?= htmlspecialchars($a['nombre_tipo_persona'] ?? 'Sin tipo') ?>"
                                    data-jefe="<?= htmlspecialchars($a['jefe_inmediato'] ?? 'Sin jefe inmediato') ?>"
                                    data-activo="<?= htmlspecialchars($a['activo_descripcion']) ?>"
                                    data-tipo-activo="<?= htmlspecialchars($a['tipo_activo']) ?>"
                                    data-serial="<?= htmlspecialchars($a['numero_serial'] ?? 'Sin número de serie') ?>"
                                    data-ip="<?= htmlspecialchars($a['numero_ip'] ?? 'Sin IP') ?>"
                                    data-mac="<?= htmlspecialchars($a['mac_address'] ?? 'Sin MAC') ?>"
                                    data-fecha-asignacion="<?= $fecha_asignacion ?: 'Sin fecha' ?>"
                                    data-fecha-retorno="<?= $fecha_retorno_display ?: 'Pendiente' ?>"
                                    data-observaciones="<?= htmlspecialchars($a['observaciones'] ?? 'Sin observaciones') ?>"
                                    data-usuario="<?= htmlspecialchars($a['usuario_asigno'] ?? 'Sin usuario') ?>"
                                    data-estado="<?= $estado_asignacion ?>"
                                    title="Ver detalles"
                                >
                                    <img src="../../img/ojo.png" alt="Ver">
                                </button>

                                <?php if ($estado_asignacion === "Activo"): ?>
                                    <!-- Botón editar -->
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= htmlspecialchars($a['id_asignacion']) ?>"
                                        data-id-persona="<?= htmlspecialchars($a['id_persona'] ?? '') ?>"
                                        data-id-activo="<?= htmlspecialchars($a['id_activo']) ?>"
                                        data-fecha-asignacion="<?= $fecha_asignacion_input ?>"
                                        data-observaciones="<?= htmlspecialchars($a['observaciones'] ?? '') ?>"
                                        title="Editar asignación"
                                    >
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>

                                    <!-- Botón retornar -->
                                    <button type="button" class="btn-icon btn-retornar"
                                        data-id="<?= htmlspecialchars($a['id_asignacion']) ?>"
                                        data-persona="<?= htmlspecialchars($a['persona_nombre']) ?>"
                                        data-activo="<?= htmlspecialchars($a['activo_descripcion']) ?>"
                                        title="Registrar retorno"
                                    >
                                        <img src="../../img/retorno.png" alt="Retornar">
                                    </button>
                                <?php endif; ?>
                                
                                <!-- Botón eliminar (para todas las asignaciones) -->
                                <button type="button" class="btn-icon btn-eliminar"
                                    data-id="<?= htmlspecialchars($a['id_asignacion']) ?>"
                                    data-persona="<?= htmlspecialchars($a['persona_nombre']) ?>"
                                    data-activo="<?= htmlspecialchars($a['activo_descripcion']) ?>"
                                    data-estado="<?= $estado_asignacion ?>"
                                    title="Eliminar asignación"
                                >
                                    <img src="../../img/eliminar.png" alt="Eliminar">
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>

<!-- Modal para nueva/editar asignación -->
<div id="modalAsignacion" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="modal-title">Nueva Asignación</h3>
        
        <form id="formAsignacion" method="POST" action="../controllers/procesar_asignacion.php">
            <input type="hidden" name="accion" id="accion" value="crear">
            <input type="hidden" name="id_asignacion" id="id_asignacion" value="">
            <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($id_usuario_sesion) ?>">
            
            <label>Persona:</label>
            <select name="id_persona" id="id_persona" required>
                <option value="">Seleccione una persona...</option>
                <?php 
                // Reset the result pointer
                sqlsrv_fetch($personas, SQLSRV_SCROLL_FIRST);
                while ($persona = sqlsrv_fetch_array($personas, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?= $persona['id_persona'] ?>"><?= htmlspecialchars($persona['nombre_completo']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Activo:</label>
            <select name="id_activo" id="id_activo" required>
                <option value="">Seleccione un activo...</option>
                <?php while ($activo = sqlsrv_fetch_array($activos_disponibles, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?= $activo['id_activo'] ?>" 
                            data-estado="<?= $activo['estado'] ?>"
                            data-estado-asignacion="<?= $activo['estado_asignacion'] ?>"
                            <?= $activo['estado_asignacion'] === 'Asignado' ? 'style="color: #999; font-style: italic;"' : '' ?>>
                        <?= htmlspecialchars($activo['descripcion_activo']) ?>
                        <?= $activo['estado_asignacion'] === 'Asignado' ? ' - [YA ASIGNADO]' : ' - [DISPONIBLE]' ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Fecha de Asignación:</label>
            <input type="date" name="fecha_asignacion" id="fecha_asignacion" value="<?= date('Y-m-d') ?>" required>

            <label>Observaciones:</label>
            <textarea name="observaciones" id="observaciones" rows="3" placeholder="Observaciones adicionales (opcional)"></textarea>

            <br>
            <button type="submit" id="btn-submit">Asignar</button>
        </form>
    </div>
</div>

<!-- Modal para retorno -->
<div id="modalRetorno" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close close-retorno">&times;</span>
        <h3>Registrar Retorno</h3>
        
        <form id="formRetorno" method="POST" action="../controllers/procesar_asignacion.php">
            <input type="hidden" name="accion" value="retornar">
            <input type="hidden" name="id_asignacion" id="retorno_id_asignacion">
            
            <div class="info-asignacion">
                <p><strong>Persona:</strong> <span id="retorno_persona"></span></p>
                <p><strong>Activo:</strong> <span id="retorno_activo"></span></p>
            </div>

            <label>Fecha de Retorno:</label>
            <input type="date" name="fecha_retorno" id="fecha_retorno" value="<?= date('Y-m-d') ?>" required>

            <label>Observaciones del Retorno:</label>
            <textarea name="observaciones_retorno" id="observaciones_retorno" rows="3" placeholder="Estado del equipo, observaciones del retorno..."></textarea>

            <br>
            <button type="submit">Registrar Retorno</button>
        </form>
    </div>
</div>

<!-- Modal para ver detalles -->
<div id="modalVisualizacion" class="modal" style="display:none;">
    <div class="modal-content detalles">
        <span class="close close-view">&times;</span>
        <h3>Detalles Completos de la Asignación</h3>
        
        <!-- Sección de documentos PDF -->
        <div class="seccion-documentos">
            <h4>📄 Documentos de Asignación</h4>
            <div class="documentos-grid">
                <div class="documento-item">
                    <strong>Documento sin Firma:</strong>
                    <div class="documento-acciones">
                        <button type="button" class="btn-generar-pdf" data-tipo="sinFirma">
                            📄 Generar Documento PDF
                        </button>
                        <button type="button" class="btn-ver-pdf" data-tipo="sinFirma" style="display:none;">
                            🔗 Abrir PDF en Nueva Pestaña
                        </button>
                    </div>
                </div>
                <div class="documento-item">
                    <strong>Documento con Firma:</strong>
                    <div class="documento-acciones">
                        <input type="file" class="input-firma" accept=".pdf" style="display:none;">
                        <button type="button" class="btn-subir-firma">
                            📤 Subir Firmado
                        </button>
                        <button type="button" class="btn-ver-pdf" data-tipo="conFirma" style="display:none;">
                            👀 Ver Firmado
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="detalles-grid">
            <!-- Sección: Información de la Persona -->
            <div class="seccion-detalles">
                <h4>👤 Información de la Persona</h4>
                <div class="detalle-item">
                    <strong>Nombre Completo:</strong>
                    <span id="view-persona"></span>
                </div>
                <div class="detalle-item">
                    <strong>Email:</strong>
                    <span id="view-email"></span>
                </div>
                <div class="detalle-item">
                    <strong>Teléfono:</strong>
                    <span id="view-telefono"></span>
                </div>
                <div class="detalle-item">
                    <strong>Localidad:</strong>
                    <span id="view-localidad"></span>
                </div>
                <div class="detalle-item">
                    <strong>Área:</strong>
                    <span id="view-area"></span>
                </div>
                <div class="detalle-item">
                    <strong>Empresa:</strong>
                    <span id="view-empresa"></span>
                </div>
                <div class="detalle-item">
                    <strong>Situación Personal:</strong>
                    <span id="view-situacion"></span>
                </div>
                <div class="detalle-item">
                    <strong>Tipo de Persona:</strong>
                    <span id="view-tipo-persona"></span>
                </div>
                <div class="detalle-item">
                    <strong>Jefe Inmediato:</strong>
                    <span id="view-jefe"></span>
                </div>
            </div>

            <!-- Sección: Información del Activo -->
            <div class="seccion-detalles">
                <h4>💻 Información del Activo</h4>
                <div class="detalle-item">
                    <strong>Activo Asignado:</strong>
                    <span id="view-activo"></span>
                </div>
                <div class="detalle-item">
                    <strong>Tipo de Activo:</strong>
                    <span id="view-tipo-activo"></span>
                </div>
                <div class="detalle-item">
                    <strong>Número de Serie:</strong>
                    <span id="view-serial"></span>
                </div>
                <div class="detalle-item">
                    <strong>Dirección IP:</strong>
                    <span id="view-ip"></span>
                </div>
                <div class="detalle-item">
                    <strong>Dirección MAC:</strong>
                    <span id="view-mac"></span>
                </div>
            </div>

            <!-- Sección: Información de la Asignación -->
            <div class="seccion-detalles">
                <h4>📅 Información de la Asignación</h4>
                <div class="detalle-item">
                    <strong>Fecha de Asignación:</strong>
                    <span id="view-fecha-asignacion"></span>
                </div>
                <div class="detalle-item">
                    <strong>Fecha de Retorno:</strong>
                    <span id="view-fecha-retorno"></span>
                </div>
                <div class="detalle-item">
                    <strong>Estado Actual:</strong>
                    <span id="view-estado"></span>
                </div>
                <div class="detalle-item">
                    <strong>Usuario que Asignó:</strong>
                    <span id="view-usuario"></span>
                </div>
                <div class="detalle-item">
                    <strong>Duración de la Asignación:</strong>
                    <span id="view-duracion"></span>
                </div>
            </div>
            
            <!-- Observaciones -->
            <div class="seccion-detalles">
                <h4>📝 Observaciones</h4>
                <div class="detalle-item observaciones-item">
                    <div id="view-observaciones" class="observaciones-texto"></div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Incluir el módulo de gestión PDF ANTES del script principal -->
    <script src="../../js/admin/pdf_asignacion/gestion_pdf.js"></script>
    <script src="../../js/admin/crud_asignacion.js"></script>
    <!-- Incluir JavaScript del Sidebar -->
    <script src="../../js/admin/sidebar.js"></script>
</body>
</html>