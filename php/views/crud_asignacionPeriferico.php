<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de asignaciones de periféricos con información actualizada
$sqlAsignacionesPerifericos = "SELECT 
    ap.id_asignacion_periferico,
    ap.id_persona,
    ap.id_periferico,
    ap.fecha_asignacion,
    ap.fecha_retorno,
    ap.observaciones,
    CONCAT(p.nombre, ' ', p.apellido) as nombre_persona,
    p.correo as email,
    p.celular as telefono,
    l.localidad_nombre,
    a.nombre as area_nombre,
    e.nombre as empresa_nombre,
    sp.situacion as situacion_personal,
    tp_persona.nombre_tipo_persona,
    CONCAT(jefe.nombre, ' ', jefe.apellido) as jefe_inmediato,
    tp.vtipo_periferico,
    m.nombre as marca_nombre,
    per.nombre_periferico,
    per.modelo,
    per.numero_serie,
    ep.vestado_periferico,
    cp.vcondicion_periferico
    FROM asignacion_periferico ap
    INNER JOIN persona p ON ap.id_persona = p.id_persona
    LEFT JOIN localidad l ON p.id_localidad = l.id_localidad
    LEFT JOIN area a ON p.id_area = a.id_area
    LEFT JOIN empresa e ON p.id_empresa = e.id_empresa
    LEFT JOIN situacion_personal sp ON p.id_situacion_personal = sp.id_situacion
    LEFT JOIN tipo_persona tp_persona ON p.id_tipo_persona = tp_persona.id_tipo_persona
    LEFT JOIN persona jefe ON p.jefe_inmediato = jefe.id_persona
    INNER JOIN periferico per ON ap.id_periferico = per.id_periferico
    INNER JOIN tipo_periferico tp ON per.id_tipo_periferico = tp.id_tipo_periferico
    INNER JOIN marca m ON per.id_marca = m.id_marca
    INNER JOIN estado_periferico ep ON per.id_estado_periferico = ep.id_estado_periferico
    INNER JOIN condicion_periferico cp ON per.id_condicion_periferico = cp.id_condicion_periferico
    ORDER BY ap.fecha_asignacion DESC";

$asignacionesPerifericos = sqlsrv_query($conn, $sqlAsignacionesPerifericos);

// Obtener personas para el dropdown
$sqlPersonas = "SELECT id_persona, CONCAT(nombre, ' ', apellido) as nombre_completo FROM persona ORDER BY nombre, apellido";
$personas = sqlsrv_query($conn, $sqlPersonas);

// Obtener periféricos disponibles (no asignados o con fecha de retorno)
$sqlPerifericos = "SELECT 
    p.id_periferico,
    tp.vtipo_periferico,
    m.nombre as marca_nombre,
    p.nombre_periferico,
    p.modelo,
    ep.vestado_periferico,
    cp.vcondicion_periferico
    FROM periferico p
    INNER JOIN tipo_periferico tp ON p.id_tipo_periferico = tp.id_tipo_periferico
    INNER JOIN marca m ON p.id_marca = m.id_marca
    INNER JOIN estado_periferico ep ON p.id_estado_periferico = ep.id_estado_periferico
    INNER JOIN condicion_periferico cp ON p.id_condicion_periferico = cp.id_condicion_periferico
    WHERE p.id_periferico NOT IN (
        SELECT id_periferico FROM asignacion_periferico WHERE fecha_retorno IS NULL
    )
    ORDER BY tp.vtipo_periferico, m.nombre";

$perifericos = sqlsrv_query($conn, $sqlPerifericos);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestión de Asignaciones de Periféricos</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
</head>

<body>
    <!-- Incluir Header -->
    <?php include('includes/header.php'); ?>

    <!-- Incluir Sidebar -->
    <?php include('includes/sidebar.php'); ?>

    <!-- Mostrar mensajes de error o éxito -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alerta-error" id="mensajeError">
            <?php if ($_GET['error'] === 'periferico_ya_asignado'): ?>
                Este periférico ya está asignado a otra persona.
            <?php elseif ($_GET['error'] === 'db'): ?>
                Error en la base de datos: <?= htmlspecialchars($_GET['message'] ?? 'Error desconocido') ?>
            <?php else: ?>
                Error: <?= htmlspecialchars($_GET['error']) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alerta-exito" id="mensajeExito">
            Operación realizada exitosamente.
        </div>
    <?php endif; ?>
    
    <main class="main-content" id="mainContent">
        <a href="vista_admin.php" class="back-button">
            <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
        </a>
        
        <div class="main-container"> 
            <div class="top-bar">
                <h2>Asignaciones de Periféricos</h2>
                <input type="text" id="buscador" placeholder="Buscar asignaciones">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaAsignacionesPerifericos">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Persona</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Serie</th>
                        <th>Estado</th>
                        <th>Fecha Asignación</th>
                        <th>Fecha Retorno</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($ap = sqlsrv_fetch_array($asignacionesPerifericos, SQLSRV_FETCH_ASSOC)) { 
                        // Determinar estado y clase CSS
                        $estado_asignacion = $ap['fecha_retorno'] ? 'Retornado' : 'Activo';
                        $estado_clase = $estado_asignacion === 'Activo' ? 'estado-asignado' : 'estado-disponible';
                        
                        // Formatear fechas
                        $fecha_asignacion = $ap['fecha_asignacion'] ? $ap['fecha_asignacion']->format('d/m/Y') : '-';
                        $fecha_retorno = $ap['fecha_retorno'] ? $ap['fecha_retorno']->format('d/m/Y') : 'Activa';
                        $fecha_asignacion_input = $ap['fecha_asignacion'] ? $ap['fecha_asignacion']->format('Y-m-d') : '';
                    ?>
                    <tr class="<?= $estado_clase ?>">
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($ap['nombre_persona']) ?></td>
                        <td><?= htmlspecialchars($ap['vtipo_periferico']) ?></td>
                        <td><?= htmlspecialchars($ap['marca_nombre']) ?></td>
                        <td><?= htmlspecialchars($ap['modelo'] ?? $ap['nombre_periferico'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($ap['numero_serie'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($ap['vestado_periferico']) ?></td>
                        <td><?= $fecha_asignacion ?></td>
                        <td><?= $fecha_retorno ?></td>
                        <td>
                            <div class="acciones">
                                <!-- Botón ver detalles -->
                                <button type="button" class="btn-icon btn-ver" 
                                    data-persona="<?= htmlspecialchars($ap['nombre_persona']) ?>"
                                    data-email="<?= htmlspecialchars($ap['email'] ?? 'Sin email') ?>"
                                    data-telefono="<?= htmlspecialchars($ap['telefono'] ?? 'Sin teléfono') ?>"
                                    data-localidad="<?= htmlspecialchars($ap['localidad_nombre'] ?? 'Sin localidad') ?>"
                                    data-area="<?= htmlspecialchars($ap['area_nombre'] ?? 'Sin área') ?>"
                                    data-empresa="<?= htmlspecialchars($ap['empresa_nombre'] ?? 'Sin empresa') ?>"
                                    data-situacion="<?= htmlspecialchars($ap['situacion_personal'] ?? 'Sin situación') ?>"
                                    data-tipo-persona="<?= htmlspecialchars($ap['nombre_tipo_persona'] ?? 'Sin tipo') ?>"
                                    data-jefe="<?= htmlspecialchars($ap['jefe_inmediato'] ?? 'Sin jefe inmediato') ?>"
                                    data-tipo-periferico="<?= htmlspecialchars($ap['vtipo_periferico']) ?>"
                                    data-marca="<?= htmlspecialchars($ap['marca_nombre']) ?>"
                                    data-modelo="<?= htmlspecialchars($ap['modelo'] ?? '') ?>"
                                    data-nombre-periferico="<?= htmlspecialchars($ap['nombre_periferico'] ?? '') ?>"
                                    data-numero-serie="<?= htmlspecialchars($ap['numero_serie'] ?? '') ?>"
                                    data-estado-periferico="<?= htmlspecialchars($ap['vestado_periferico']) ?>"
                                    data-condicion="<?= htmlspecialchars($ap['vcondicion_periferico'] ?? '') ?>"
                                    data-fecha-asignacion="<?= $fecha_asignacion ?>"
                                    data-fecha-retorno="<?= $fecha_retorno === 'Activa' ? 'Pendiente' : $fecha_retorno ?>"
                                    data-estado="<?= $estado_asignacion ?>"
                                    data-observaciones="<?= htmlspecialchars($ap['observaciones'] ?? '') ?>"
                                    title="Ver detalles"
                                >
                                    <img src="../../img/ojo.png" alt="Ver">
                                </button>

                                <?php if ($estado_asignacion === 'Activo'): ?>
                                    <!-- Botón editar (solo para asignaciones activas) -->
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id-asignacion-periferico="<?= $ap['id_asignacion_periferico'] ?>"
                                        data-id-persona="<?= $ap['id_persona'] ?>"
                                        data-id-periferico="<?= $ap['id_periferico'] ?>"
                                        data-fecha-asignacion="<?= $fecha_asignacion_input ?>"
                                        data-observaciones="<?= htmlspecialchars($ap['observaciones'] ?? '') ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>

                                    <!-- Botón retornar -->
                                    <button type="button" class="btn-icon btn-retornar"
                                        data-id="<?= $ap['id_asignacion_periferico'] ?>"
                                        data-persona="<?= htmlspecialchars($ap['nombre_persona']) ?>"
                                        data-periferico="<?= htmlspecialchars($ap['vtipo_periferico'] . ' - ' . $ap['marca_nombre'] . ' ' . ($ap['modelo'] ?? $ap['nombre_periferico'] ?? '')) ?>"
                                        title="Registrar retorno">
                                        <img src="../../img/retorno.png" alt="Retornar">
                                    </button>
                                <?php endif; ?>

                                <!-- Botón eliminar (para todas las asignaciones) -->
                                <button type="button" class="btn-icon btn-eliminar"
                                    data-id="<?= $ap['id_asignacion_periferico'] ?>"
                                    data-persona="<?= htmlspecialchars($ap['nombre_persona']) ?>"
                                    data-periferico="<?= htmlspecialchars($ap['vtipo_periferico'] . ' - ' . $ap['marca_nombre'] . ' ' . ($ap['modelo'] ?? $ap['nombre_periferico'] ?? '')) ?>"
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
    <!-- Modal para Crear o Editar -->
    <div id="modalAsignacionPeriferico" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title">Crear Asignación de Periférico</h2>
            <form id="formAsignacionPeriferico" method="POST" action="../controllers/procesar_asignacionPeriferico.php">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id_asignacion_periferico" id="id_asignacion_periferico">

                <label for="persona">Persona:</label>
                <select id="persona" name="persona" required>
                    <option value="">Seleccione una persona...</option>
                    <?php 
                    // Reset pointer for personas
                    $personasArray = [];
                    while ($p = sqlsrv_fetch_array($personas, SQLSRV_FETCH_ASSOC)) {
                        $personasArray[] = $p;
                    }
                    foreach ($personasArray as $p) { ?>
                        <option value="<?= $p['id_persona'] ?>"><?= htmlspecialchars($p['nombre_completo']) ?></option>
                    <?php } ?>
                </select>

                <label for="periferico">Periférico:</label>
                <select id="periferico" name="periferico" required>
                    <option value="">Seleccione un periférico...</option>
                    <?php 
                    // Reset pointer for perifericos
                    $perifericosArray = [];
                    while ($p = sqlsrv_fetch_array($perifericos, SQLSRV_FETCH_ASSOC)) {
                        $perifericosArray[] = $p;
                    }
                    foreach ($perifericosArray as $p) { 
                        $descripcion = $p['vtipo_periferico'] . ' - ' . $p['marca_nombre'];
                        if (!empty($p['modelo'])) {
                            $descripcion .= ' - ' . $p['modelo'];
                        } elseif (!empty($p['nombre_periferico'])) {
                            $descripcion .= ' - ' . $p['nombre_periferico'];
                        }
                        $descripcion .= ' (' . $p['vestado_periferico'] . ')';
                    ?>
                        <option value="<?= $p['id_periferico'] ?>"><?= htmlspecialchars($descripcion) ?></option>
                    <?php } ?>
                </select>

                <label for="fecha_asignacion">Fecha de Asignación:</label>
                <input type="date" id="fecha_asignacion" name="fecha_asignacion" required>

                <label for="observaciones">Observaciones:</label>
                <textarea id="observaciones" name="observaciones" rows="3"></textarea>

                <button type="submit">Guardar</button>
            </form>
        </div>
    </div>     

    <!-- Modal para retorno -->
    <div id="modalRetorno" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close close-retorno">&times;</span>
            <h3>Registrar Retorno de Periférico</h3>
            
            <form id="formRetorno" method="POST" action="../controllers/procesar_asignacionPeriferico.php">
                <input type="hidden" name="accion" value="retornar">
                <input type="hidden" name="id_asignacion_periferico" id="retorno_id_asignacion">
                
                <div class="info-asignacion">
                    <p><strong>Persona:</strong> <span id="retorno_persona"></span></p>
                    <p><strong>Periférico:</strong> <span id="retorno_periferico"></span></p>
                </div>

                <label>Fecha de Retorno:</label>
                <input type="date" name="fecha_retorno" id="fecha_retorno" value="<?= date('Y-m-d') ?>" required>

                <label>Observaciones del Retorno:</label>
                <textarea name="observaciones_retorno" id="observaciones_retorno" rows="3" placeholder="Estado del periférico, observaciones del retorno..."></textarea>

                <br>
                <button type="submit">Registrar Retorno</button>
            </form>
        </div>
    </div>

    <!-- Modal para ver detalles -->
    <div id="modalVisualizacion" class="modal">
        <div class="modal-content detalles">
            <span class="close close-view">&times;</span>
            <h3>Detalles Completos de la Asignación de Periférico</h3>
            
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

                <!-- Sección: Información del Periférico -->
                <div class="seccion-detalles">
                    <h4>🖱️ Información del Periférico</h4>
                    <div class="detalle-item">
                        <strong>Tipo de Periférico:</strong>
                        <span id="view-tipo-periferico"></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Marca:</strong>
                        <span id="view-marca"></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Modelo:</strong>
                        <span id="view-modelo"></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Nombre del Periférico:</strong>
                        <span id="view-nombre-periferico"></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Número de Serie:</strong>
                        <span id="view-numero-serie"></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Estado:</strong>
                        <span id="view-estado-periferico"></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Condición:</strong>
                        <span id="view-condicion"></span>
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
                        <strong>Duración de la Asignación:</strong>
                        <span id="view-duracion"></span>
                    </div>
                </div>
                
                <!-- Observaciones -->
                <div class="seccion-detalles ">
                    <h4>📝 Observaciones</h4>
                    <div class="detalle-item observaciones-item">
                        <div id="view-observaciones" class="observaciones-texto"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../js/admin/crud_asignacionPeriferico.js"></script>
    
    <!-- Incluir JavaScript del Sidebar -->
    <script src="../../js/admin/sidebar.js"></script>
</body>
</html>
