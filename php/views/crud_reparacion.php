<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// CORREGIDO: Obtener reparaciones con nombres de campos correctos de la BD
$sqlReparaciones = "SELECT r.*, 
                           CASE 
                               WHEN a.id_laptop IS NOT NULL THEN ISNULL(l.nombreEquipo, 'Sin nombre')
                               WHEN a.id_pc IS NOT NULL THEN ISNULL(p.nombreEquipo, 'Sin nombre') 
                               WHEN a.id_servidor IS NOT NULL THEN ISNULL(s.nombreEquipo, 'Sin nombre')
                               ELSE 'Activo sin tipo'
                           END as nombre_equipo,
                           ISNULL(a.tipo_activo, 'Sin tipo') as tipo_activo,
                           ISNULL(lr.nombre_lugar, 'Sin lugar') as nombre_lugar,
                           ISNULL(er.nombre_estado, 'Sin estado') as nombre_estado,
                           ISNULL(prov.nombre, '-') as nombre_proveedor,
                           CASE 
                               WHEN asig.id_persona IS NOT NULL THEN CONCAT(per.nombre, ' ', per.apellido)
                               ELSE 'Sin asignar'
                           END as persona_asignada
                    FROM reparacion r
                    INNER JOIN activo a ON r.id_activo = a.id_activo
                    LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
                    LEFT JOIN pc p ON a.id_pc = p.id_pc
                    LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
                    LEFT JOIN lugar_reparacion lr ON r.id_lugar_reparacion = lr.id_lugar
                    LEFT JOIN estado_reparacion er ON r.id_estado_reparacion = er.id_estado_reparacion
                    LEFT JOIN proveedor prov ON r.id_proveedor = prov.id_proveedor
                    LEFT JOIN (
                        SELECT id_activo, id_persona, 
                               ROW_NUMBER() OVER (PARTITION BY id_activo ORDER BY fecha_asignacion DESC) as rn
                        FROM asignacion 
                        WHERE fecha_retorno IS NULL
                    ) asig ON a.id_activo = asig.id_activo AND asig.rn = 1
                    LEFT JOIN persona per ON asig.id_persona = per.id_persona
                    ORDER BY r.fecha DESC";
$reparaciones = sqlsrv_query($conn, $sqlReparaciones);

// CORREGIDO: Obtener catálogos con nombres de campos correctos de la BD
$sqlActivos = "SELECT a.id_activo, a.tipo_activo,
                      CASE 
                          WHEN a.id_laptop IS NOT NULL THEN ISNULL(l.nombreEquipo, 'Sin nombre')
                          WHEN a.id_pc IS NOT NULL THEN ISNULL(p.nombreEquipo, 'Sin nombre')
                          WHEN a.id_servidor IS NOT NULL THEN ISNULL(s.nombreEquipo, 'Sin nombre')
                          ELSE 'Activo sin tipo'
                      END as nombre_equipo
               FROM activo a
               LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
               LEFT JOIN pc p ON a.id_pc = p.id_pc
               LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
               ORDER BY nombre_equipo";
$activos = sqlsrv_query($conn, $sqlActivos);

$sqlLugares = "SELECT * FROM lugar_reparacion ORDER BY nombre_lugar";
$lugares = sqlsrv_query($conn, $sqlLugares);

$sqlEstados = "SELECT * FROM estado_reparacion ORDER BY nombre_estado";
$estados = sqlsrv_query($conn, $sqlEstados);

$sqlProveedores = "SELECT id_proveedor, nombre FROM proveedor ORDER BY nombre";
$proveedores = sqlsrv_query($conn, $sqlProveedores);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reparaciones</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
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
                <h2>Gestión de Reparaciones</h2>
                <input type="text" id="buscador" placeholder="Buscar reparaciones">
                <button id="btnNuevo">+ NUEVA REPARACIÓN</button>
            </div>

            <table id="tablaReparaciones">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Activo</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Lugar</th>
                        <th>Proveedor</th>
                        <th>Costo de Reparación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($r = sqlsrv_fetch_array($reparaciones, SQLSRV_FETCH_ASSOC)) { 
                        // NUEVO: Debug del tiempo de inactividad
                        error_log("DEBUG PHP: ID Reparación: {$r['id_reparacion']}, Tiempo inactividad: " . var_export($r['tiempo_inactividad'], true) . " (Tipo: " . gettype($r['tiempo_inactividad']) . ")");
                    ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($r["nombre_equipo"]) ?></td>
                            <td><?= $r["fecha"] ? $r["fecha"]->format('d/m/Y') : 'Sin fecha' ?></td>
                            <td><?= htmlspecialchars($r["nombre_estado"]) ?></td>
                            <td><?= htmlspecialchars($r["nombre_lugar"]) ?></td>
                            <td><?= htmlspecialchars($r["nombre_proveedor"]) ?></td>
                            <td><?= $r["costo"] ? '$ ' . number_format($r["costo"], 2) : '-' ?></td>
                            <td>
                                <div class="acciones">
                                    <!-- Botón ver -->
                                    <button type="button" class="btn-icon btn-ver"
                                        data-id-reparacion="<?= $r['id_reparacion'] ?>"
                                        data-fecha="<?= $r['fecha'] ? $r['fecha']->format('d/m/Y') : 'Sin fecha' ?>"
                                        data-nombre-estado="<?= htmlspecialchars($r['nombre_estado']) ?>"
                                        data-nombre-lugar="<?= htmlspecialchars($r['nombre_lugar']) ?>"
                                        data-nombre-proveedor="<?= htmlspecialchars($r['nombre_proveedor']) ?>"
                                        data-costo="<?= $r['costo'] ?>"
                                        data-tiempo-inactividad="<?= 
                                            // CORREGIDO: Manejo más específico del tiempo de inactividad
                                            $r['tiempo_inactividad'] !== null ? (string)$r['tiempo_inactividad'] : ''
                                        ?>"
                                        data-nombre-equipo="<?= htmlspecialchars($r['nombre_equipo']) ?>"
                                        data-tipo-activo="<?= htmlspecialchars($r['tipo_activo']) ?>"
                                        data-id-activo="<?= $r['id_activo'] ?>"
                                        data-persona-asignada="<?= htmlspecialchars($r['persona_asignada']) ?>"
                                        data-descripcion="<?= htmlspecialchars($r['descripcion'] ?? '') ?>">
                                        <img src="../../img/ojo.png" alt="Ver">
                                    </button>
                                    
                                    <!-- Botón editar -->
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $r['id_reparacion'] ?>"
                                        data-id-activo="<?= $r['id_activo'] ?>"
                                        data-id-lugar="<?= $r['id_lugar_reparacion'] ?>"
                                        data-id-estado="<?= $r['id_estado_reparacion'] ?>"
                                        data-id-proveedor="<?= $r['id_proveedor'] ?>"
                                        data-fecha="<?= $r['fecha'] ? $r['fecha']->format('Y-m-d') : '' ?>"
                                        data-descripcion="<?= htmlspecialchars($r['descripcion'] ?? '') ?>"
                                        data-costo="<?= $r['costo'] ?>"
                                        data-tiempo="<?= 
                                            // CORREGIDO: Manejo más específico del tiempo de inactividad para edición
                                            $r['tiempo_inactividad'] !== null ? (string)$r['tiempo_inactividad'] : ''
                                        ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                                                
                                    <!-- Botón cambios de hardware -->
                                    <button type="button" class="btn-icon btn-hardware"
                                        data-id="<?= $r['id_reparacion'] ?>"
                                        data-activo="<?= $r['id_activo'] ?>"
                                        title="Gestionar cambios de hardware">
                                        <img src="../../img/hardware.png" alt="Hardware">
                                    </button>

                                    <!-- Botón eliminar -->
                                    <form method="POST" action="../controllers/procesar_reparacion.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta reparación?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_reparacion" value="<?= $r['id_reparacion'] ?>">
                                        <button type="submit" class="btn-icon">
                                            <img src="../../img/eliminar.png" alt="Eliminar">
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Modal para crear/editar reparación -->
            <div id="modalReparacion" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2 id="modal-title">Nueva Reparación</h2>
                    <form method="POST" action="../controllers/procesar_reparacion.php" id="formReparacion">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_reparacion" id="id_reparacion">

                        <label>Activo *</label>
                        <select name="id_activo" id="id_activo" required>
                            <option value="">Seleccionar activo...</option>
                            <?php while ($activo = sqlsrv_fetch_array($activos, SQLSRV_FETCH_ASSOC)) { ?>
                                <option value="<?= $activo['id_activo'] ?>"><?= htmlspecialchars($activo['nombre_equipo']) ?> (<?= htmlspecialchars($activo['tipo_activo']) ?>)</option>
                            <?php } ?>
                        </select>

                        <!-- CORREGIDO: Mejorar el input de fecha -->
                        <label>Fecha *</label>
                        <input type="date" 
                            name="fecha" 
                            id="fecha" 
                            required 
                            max="<?= date('Y-m-d') ?>"
                            title="Seleccione la fecha de la reparación (no puede ser futura)">

                        <label>Lugar de Reparación *</label>
                        <select name="id_lugar_reparacion" id="id_lugar_reparacion" required>
                            <option value="">Seleccionar lugar...</option>
                            <?php while ($lugar = sqlsrv_fetch_array($lugares, SQLSRV_FETCH_ASSOC)) { ?>
                                <option value="<?= $lugar['id_lugar'] ?>"><?= htmlspecialchars($lugar['nombre_lugar']) ?></option>
                            <?php } ?>
                        </select>

                        <!-- NUEVO: Campo de proveedor (oculto por defecto) -->
                        <div id="proveedorField" style="display: none;">
                            <label>Proveedor *</label>
                            <select name="id_proveedor" id="id_proveedor">
                                <option value="">Seleccionar proveedor...</option>
                                <?php while ($proveedor = sqlsrv_fetch_array($proveedores, SQLSRV_FETCH_ASSOC)) { ?>
                                    <option value="<?= $proveedor['id_proveedor'] ?>"><?= htmlspecialchars($proveedor['nombre']) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <label>Estado *</label>
                        <select name="id_estado_reparacion" id="id_estado_reparacion" required>
                            <option value="">Seleccionar estado...</option>
                            <?php while ($estado = sqlsrv_fetch_array($estados, SQLSRV_FETCH_ASSOC)) { ?>
                                <option value="<?= $estado['id_estado_reparacion'] ?>"><?= htmlspecialchars($estado['nombre_estado']) ?></option>
                            <?php } ?>
                        </select>

                        <label>Costo de Reparacion</label>
                        <input type="number" step="0.01" name="costo" id="costo" min="0" placeholder="0.00">

                        <!-- CORREGIDO: Agregar validación min="0" para tiempo de inactividad -->
                        <label>Días de Inactividad</label>
                        <input type="number" name="tiempo_inactividad" id="tiempo_inactividad" min="0" placeholder="0">

                        <label>Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="3"></textarea>

                        <button type="submit" id="btn-Guardar">Guardar</button>
                    </form>
                </div>
            </div>

            <!-- Modal para ver detalles de reparación -->
            <div id="modalVisualizacion" class="modal">
                <div class="modal-content detalles">
                    <span class="close close-view">&times;</span>
                    <h3>Detalles de la Reparación</h3>
                    
                    <!-- NUEVO: Agrupar información superior -->
                    <div class="detalles-superior">
                        <div class="detalles-grid">
                            <!-- Información básica de la reparación -->
                            <div class="seccion-detalles">
                                <h4>Información de la Reparación</h4>
                                <div class="detalle-item">
                                    <strong>ID Reparación:</strong>
                                    <span id="view-id-reparacion"></span>
                                </div>
                                <div class="detalle-item">
                                    <strong>Fecha de Reparación:</strong>
                                    <span id="view-fecha"></span>
                                </div>
                                <div class="detalle-item">
                                    <strong>Estado:</strong>
                                    <span id="view-estado"></span>
                                </div>
                                <div class="detalle-item">
                                    <strong>Lugar de Reparación:</strong>
                                    <span id="view-lugar"></span>
                                </div>
                                <div class="detalle-item">
                                    <strong>Proveedor:</strong>
                                    <span id="view-proveedor"></span>
                                </div>
                                <div class="detalle-item">
                                    <strong>Costo:</strong>
                                    <span id="view-costo"></span>
                                </div>
                                <div class="detalle-item">
                                    <strong>Tiempo de Inactividad:</strong>
                                    <span id="view-tiempo-inactividad"></span>
                                </div>
                            </div>

                            <!-- Información del activo -->
                            <div class="seccion-detalles">
                                <h4>Información del Activo</h4>
                                <div class="detalle-item">
                                    <strong>Nombre del Equipo:</strong>
                                    <span id="view-nombre-equipo"></span>
                                </div>
                                <div class="detalle-item">
                                    <strong>Tipo de Activo:</strong>
                                    <span id="view-tipo-activo"></span>
                                </div>
                                <div class="detalle-item">
                                    <strong>ID del Activo:</strong>
                                    <span id="view-id-activo"></span>
                                </div>
                                <div class="detalle-item">
                                    <strong>Persona Asignada:</strong>
                                    <span id="view-persona-asignada"></span>
                                </div>
                            </div>

                            <!-- Descripción de la reparación -->
                            <div class="seccion-detalles">
                                <h4>Descripción del Problema</h4>
                                <div class="detalle-item descripcion-item">
                                    <div id="view-descripcion" class="descripcion-texto"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cambios de hardware (tabla abajo) -->
                    <div class="seccion-detalles" style="margin-top: 24px;">
                        <h4>Cambios de Hardware</h4>
                        <div class="detalle-item cambios-item">
                            <table id="tablaCambiosDetalle" style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th>Tipo de Cambio</th>
                                        <th>Tipo de Componente</th>
                                        <th>Componente Nuevo</th>
                                        <th>Componente Retirado</th>
                                        <th>Costo</th>
                                        <th>Motivo</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyCambiosDetalle">
                                    <!-- Se llenará por JavaScript -->
                                </tbody>
                            </table>
                            <div id="mensajeSinCambios" style="display:none; color:#888; margin-top:8px;">No hay cambios de hardware registrados para esta reparación.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para gestionar cambios de hardware -->
            <div id="modalCambiosHardware" class="modal">
                <div class="modal-content cambios-hardware">
                    <span class="close close-hardware">&times;</span>
                    <h3>Cambios de Hardware - Reparación #<span id="numReparacion"></span></h3>
                    
                    <div class="hardware-actions">
                        <button id="btnNuevoCambio" class="btn-nuevo-cambio">+ Agregar Cambio de Hardware</button>
                    </div>
                    
                    <!-- Tabla de cambios existentes -->
                    <table id="tablaCambiosHardware">
                        <thead>
                            <tr>
                                <th>Tipo de Cambio</th>
                                <th>Componente</th>
                                <th>Componente Nuevo</th>
                                <th>Componente Retirado</th>
                                <th>Costo</th>
                                <th>Motivo</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llenarán por JavaScript -->
                        </tbody>
                    </table>
                    
                    <!-- Formulario para agregar/editar cambio -->
                    <div id="formCambioHardware" style="display:none;" class="form-cambio">
                        <h4>Nuevo Cambio de Hardware</h4>
                        <form id="formCambio">
                            <input type="hidden" name="id_reparacion" id="idReparacionCambio">
                            <input type="hidden" name="id_activo" id="idActivoCambio">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Tipo de Cambio *</label>
                                    <select name="id_tipo_cambio" id="idTipoCambio" required>
                                        <option value="">Seleccionar tipo...</option>
                                        <!-- Se llenarán por AJAX consultando lo registrado en la db -->
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Tipo de Componente *</label>
                                    <select name="tipo_componente" id="tipoComponente" required>
                                        <option value="">Seleccionar tipo...</option>
                                        <option value="procesador">Procesador</option>
                                        <option value="ram">RAM</option>
                                        <option value="almacenamiento">Almacenamiento</option>
                                        <option value="tarjeta_video">Tarjeta de Video</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Fecha del Cambio *</label>
                                    <input type="date" name="fecha_cambio" id="fechaCambio" required max="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <!-- ÚNICO select para componente actual (solo se muestra para reemplazo/retiro) -->
                            <div id="componenteActualDiv" class="form-row" style="display:none;">
                                <div class="form-group">
                                    <label>Componente a Reemplazar/Retirar *</label>
                                    <select name="componente_actual" id="componenteActual">
                                        <option value="">Seleccionar componente actual...</option>
                                    </select>
                                    <small class="form-help">Componentes instalados actualmente en este activo</small>
                                </div>
                            </div>

                            <!-- Sección para seleccionar componente del catálogo -->
                            <div id="seccionComponenteExistente" class="form-row" style="display:none;">
                                <!-- Filtro para tipos de componentes -->
                                <div class="form-group">
                                    <label>Filtrar componentes:</label>
                                    <div class="filtro-componentes">
                                        <button type="button" id="toggleTipoComponenteReparacion" class="btn-toggle-tipo" data-tipo="todos">
                                            Mostrar Todos
                                        </button>
                                        <span id="estadoFiltroReparacion" class="estado-filtro">(Genéricos y Detallados)</span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Seleccionar Componente *</label>
                                    <select name="id_componente_existente" id="idComponenteExistente" required>
                                        <option value="">Primero seleccione el tipo de componente</option>
                                    </select>
                                    <small class="form-help">Componentes disponibles del catálogo (genéricos y detallados)</small>
                                </div>
                            </div>

                            <!-- Campo oculto para indicar que siempre usamos componente existente -->
                            <input type="hidden" name="tipo_nuevo_componente" value="usar_existente">

                            <!-- Resto del formulario sin cambios -->

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Costo del Hardware</label>
                                    <input type="number" step="0.01" name="costo" id="costoCambio" min="0"
                                        placeholder="0.00">
                                </div>
                                
                                <div class="form-group">
                                    <label>Motivo del Cambio</label>
                                    <input type="text" name="motivo" id="motivoCambio" 
                                        placeholder="Motivo o razón del cambio">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" onclick="guardarCambioHardware()" class="btn-guardar">
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Template para filas de cambios de hardware -->
            <template id="templateFilaCambioHardware">
                <tr>
                    <td class="tipo-cambio"></td>
                    <td class="tipo-componente"></td>
                    <td class="componente-nuevo"></td>
                    <td class="componente-retirado"></td>
                    <td class="costo-cambio"></td>
                    <td class="motivo-cambio"></td>
                    <td>
                        <button type="button" class="btn-icon btn-eliminar" title="Eliminar cambio">
                            <img src="../../img/eliminar.png" alt="Eliminar">
                        </button>
                    </td>
                </tr>
            </template>
        </div>
    </main>
    <!-- Scripts principales del sistema -->
    <script src="../../js/admin/reparacion/utils.js"></script>
    <script src="../../js/admin/reparacion/hardware_fixed.js"></script>
    <script src="../../js/admin/reparacion/eventos.js"></script>
    <script src="../../js/admin/crud_reparacion.js"></script>
    
    <!-- Módulos específicos -->
    <script src="../../js/admin/reparacion/fix_validacion.js"></script>
    <script src="../../js/admin/reparacion/patch_componentes.js"></script>
    <script src="../../js/admin/reparacion/tabla_cambios.js"></script>

    <!-- Incluir JavaScript del Sidebar -->
    <script src="../../js/admin/sidebar.js"></script>
    <script>
        // ========================================
        // MANEJO DE ENVÍO DEL FORMULARIO POR AJAX
        // ========================================
        document.addEventListener("DOMContentLoaded", function() {
            const formReparacion = document.getElementById('formReparacion');
            
            if (formReparacion) {
                formReparacion.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevenir envío tradicional
                    
                    // Crear FormData desde el formulario
                    const formData = new FormData(this);
                    
                    // Mostrar indicador de carga
                    const btnGuardar = document.getElementById('btn-Guardar');
                    const textoOriginal = btnGuardar.textContent;
                    btnGuardar.disabled = true;
                    btnGuardar.textContent = 'Guardando...';
                    
                    // Enviar por AJAX
                    fetch('../controllers/procesar_reparacion.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        const contentType = response.headers.get('content-type');
                        
                        // Si es JSON, parsearlo
                        if (contentType && contentType.includes('application/json')) {
                            return response.json().then(data => {
                                if (!response.ok) {
                                    throw new Error(data.error || 'Error en la operación');
                                }
                                return data;
                            });
                        }
                        
                        // Si no es JSON, es una redirección exitosa tradicional
                        if (response.ok) {
                            return { success: true, redirect: true };
                        }
                        
                        throw new Error('Error en la operación');
                    })
                    .then(data => {
                        if (data.success) {
                            alert('✅ Reparación guardada correctamente');
                            
                            // Cerrar modal
                            document.getElementById('modalReparacion').style.display = 'none';
                            
                            // Recargar la página para actualizar la tabla
                            window.location.reload();
                        } else if (data.error) {
                            alert('❌ Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('❌ Error: ' + error.message);
                    })
                    .finally(() => {
                        // Restaurar botón
                        btnGuardar.disabled = false;
                        btnGuardar.textContent = textoOriginal;
                    });
                });
            }
        });

        // NUEVO: Función para mostrar/ocultar campo de proveedor
        document.addEventListener("DOMContentLoaded", function() {
            const lugarSelect = document.getElementById('id_lugar_reparacion');
            const proveedorField = document.getElementById('proveedorField');
            const proveedorSelect = document.getElementById('id_proveedor');

            function toggleProveedorField() {
                const selectedOption = lugarSelect.options[lugarSelect.selectedIndex];
                const lugarNombre = selectedOption.text.toLowerCase();
                
                if (lugarNombre.includes('proveedor externo')) {
                    proveedorField.style.display = 'block';
                    proveedorSelect.required = true;
                } else {
                    proveedorField.style.display = 'none';
                    proveedorSelect.required = false;
                    proveedorSelect.value = '';
                }
            }

            lugarSelect.addEventListener('change', toggleProveedorField);
            
            // Verificar al cargar la página (para modo edición)
            toggleProveedorField();
        });

        // NUEVO: Función para cargar y mostrar los cambios de hardware en el modal de detalles
        function cargarCambiosDetalle(idReparacion) {
            const tbody = document.getElementById('tbodyCambiosDetalle');
            const mensajeSinCambios = document.getElementById('mensajeSinCambios');
            tbody.innerHTML = '';
            mensajeSinCambios.style.display = 'none';

            fetch(`../controllers/procesar_reparacion.php?action=get_cambios_hardware&id_reparacion=${idReparacion}`)
                .then(res => res.json())
                .then(data => {
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(cambio => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${cambio.nombre_tipo_cambio || cambio.tipo_cambio || '-'}</td>
                                <td>${cambio.tipo_componente || '-'}</td>
                                <td>${cambio.componente_nuevo || '-'}</td>
                                <td>${cambio.componente_retirado || '-'}</td>
                                <td>${cambio.costo !== undefined ? '$' + parseFloat(cambio.costo).toFixed(2) : '-'}</td>
                                <td>${cambio.motivo || '-'}</td>
                                <td>${cambio.fecha_formateada || cambio.fecha || '-'}</td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        mensajeSinCambios.style.display = 'block';
                    }
                })
                .catch(err => {
                    mensajeSinCambios.textContent = 'Error cargando cambios de hardware.';
                    mensajeSinCambios.style.display = 'block';
                });
        }

        // MODIFICADO: Cuando se abre el modal de detalles, cargar los cambios de hardware
        document.querySelectorAll('.btn-ver').forEach(btn => {
            btn.addEventListener('click', function() {
                // Llenar campos existentes
                document.getElementById('view-id-reparacion').textContent = this.getAttribute('data-id-reparacion');
                document.getElementById('view-fecha').textContent = this.getAttribute('data-fecha');
                document.getElementById('view-estado').textContent = this.getAttribute('data-nombre-estado');
                document.getElementById('view-lugar').textContent = this.getAttribute('data-nombre-lugar');
                document.getElementById('view-proveedor').textContent = this.getAttribute('data-nombre-proveedor');
                document.getElementById('view-costo').textContent = this.getAttribute('data-costo') ? '$' + parseFloat(this.getAttribute('data-costo')).toFixed(2) : '-';
                document.getElementById('view-tiempo-inactividad').textContent = this.getAttribute('data-tiempo-inactividad') ? this.getAttribute('data-tiempo-inactividad') + ' días' : '-';
                document.getElementById('view-nombre-equipo').textContent = this.getAttribute('data-nombre-equipo');
                document.getElementById('view-tipo-activo').textContent = this.getAttribute('data-tipo-activo');
                document.getElementById('view-id-activo').textContent = this.getAttribute('data-id-activo');
                document.getElementById('view-persona-asignada').textContent = this.getAttribute('data-persona-asignada');
                document.getElementById('view-descripcion').textContent = this.getAttribute('data-descripcion') || 'Sin descripción';
                
                const idReparacion = this.getAttribute('data-id-reparacion');
                cargarCambiosDetalle(idReparacion);
                
                document.getElementById('modalVisualizacion').style.display = 'block';
            });
        });

        // MODIFICADO: Cuando se abre el modal de edición, manejar el campo de proveedor
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modal-title').textContent = 'Editar Reparación';
                document.getElementById('accion').value = 'editar';
                document.getElementById('id_reparacion').value = this.getAttribute('data-id');
                document.getElementById('id_activo').value = this.getAttribute('data-id-activo');
                document.getElementById('id_lugar_reparacion').value = this.getAttribute('data-id-lugar');
                document.getElementById('id_estado_reparacion').value = this.getAttribute('data-id-estado');
                document.getElementById('id_proveedor').value = this.getAttribute('data-id-proveedor') || '';
                document.getElementById('fecha').value = this.getAttribute('data-fecha');
                document.getElementById('descripcion').value = this.getAttribute('data-descripcion');
                document.getElementById('costo').value = this.getAttribute('data-costo') || '';
                document.getElementById('tiempo_inactividad').value = this.getAttribute('data-tiempo') || '';
                
                // Verificar si se debe mostrar el campo de proveedor
                setTimeout(() => {
                    const lugarSelect = document.getElementById('id_lugar_reparacion');
                    const event = new Event('change');
                    lugarSelect.dispatchEvent(event);
                }, 100);
                
                document.getElementById('modalReparacion').style.display = 'block';
            });
        });
    </script>
</body>
</html>
