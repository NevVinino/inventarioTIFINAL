<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Recuperar datos de sesión
$id_usuario_sesion = $_SESSION['id_usuario'] ?? '';
$nombre_usuario_sesion = $_SESSION['username'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Asignaciones</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/dashboard.css">
    <link rel="stylesheet" href="../../css/admin/crud_historial_asignacion.css">
</head>
<body>

    <!-- Incluir Header -->
    <?php include('includes/header.php'); ?>

    <!-- Incluir Sidebar -->
    <?php include('includes/sidebar.php'); ?>

    <main class="main-content" id="mainContent">
        <!-- Botón volver -->
        <div class="back-container">
            <a href="vista_admin.php" class="back-button">
                <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
            </a>
        </div>

        <!-- Título -->
        <div class="dashboard-header-title">
            <h1>📋 Historial de Asignaciones</h1>
            <p>Consulta la trazabilidad completa de asignaciones de activos</p>
        </div>

        <!-- Contenedor principal - layout vertical -->
        <div class="contenido-principal">
            
            <!-- Panel de filtros de búsqueda - ancho completo -->
            <div class="filtros-container">
                <div class="filtros-card">
                    <h3>🔍 Filtros de Búsqueda</h3>
                    <div class="filtros-grid">
                        <div class="filtro-grupo">
                            <label for="buscar_numero_serial">Número Serial:</label>
                            <input type="text" id="buscar_numero_serial" placeholder="Ingrese número serial">
                        </div>
                        <div class="filtro-grupo">
                            <label for="buscar_nombre_activo">Nombre del Activo:</label>
                            <input type="text" id="buscar_nombre_activo" placeholder="Ingrese nombre del equipo">
                        </div>
                        <div class="filtro-grupo">
                            <label for="buscar_persona">Persona Asignada:</label>
                            <input type="text" id="buscar_persona" placeholder="Ingrese nombre de la persona">
                        </div>
                        <div class="filtro-grupo">
                            <label for="filtro_tipo_activo">Tipo de Activo:</label>
                            <select id="filtro_tipo_activo">
                                <option value="">Todos los tipos</option>
                                <option value="Laptop">Laptop</option>
                                <option value="PC">PC</option>
                                <option value="Servidor">Servidor</option>
                            </select>
                        </div>
                        <div class="filtro-grupo">
                            <label for="filtro_estado_asignacion">Estado:</label>
                            <select id="filtro_estado_asignacion">
                                <option value="">Todos los estados</option>
                                <option value="Activo">Activo</option>
                                <option value="Retornado">Retornado</option>
                            </select>
                        </div>
                        <div class="filtro-grupo">
                            <label for="filtro_fecha_desde">Fecha Desde:</label>
                            <input type="date" id="filtro_fecha_desde">
                        </div>
                        <div class="filtro-grupo">
                            <label for="filtro_fecha_hasta">Fecha Hasta:</label>
                            <input type="date" id="filtro_fecha_hasta">
                        </div>
                        <div class="filtro-botones">
                            <button type="button" id="btn_buscar" class="btn btn-primary">
                                🔍 Buscar
                            </button>
                            <button type="button" id="btn_limpiar" class="btn btn-secondary">
                                🧹 Limpiar
                            </button>
                            <button type="button" id="btn_exportar" class="btn btn-success">
                                📄 Exportar
                            </button>
                            <button type="button" id="btn_archivos" class="btn btn-info">
                                📁 Archivos PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas en fila horizontal -->
            <div class="estadisticas-container">
                <div class="estadistica-card">
                    <div class="estadistica-icono">📦</div>
                    <div class="estadistica-info">
                        <h3 id="total_asignaciones">-</h3>
                        <p>Total Asignaciones</p>
                    </div>
                </div>
                <div class="estadistica-card">
                    <div class="estadistica-icono">✅</div>
                    <div class="estadistica-info">
                        <h3 id="asignaciones_activas">-</h3>
                        <p>Asignaciones Activas</p>
                    </div>
                </div>
                <div class="estadistica-card">
                    <div class="estadistica-icono">🔄</div>
                    <div class="estadistica-info">
                        <h3 id="asignaciones_retornadas">-</h3>
                        <p>Asignaciones Retornadas</p>
                    </div>
                </div>
                <div class="estadistica-card">
                    <div class="estadistica-icono">⏱️</div>
                    <div class="estadistica-info">
                        <h3 id="promedio_dias">-</h3>
                        <p>Promedio Días Asignación</p>
                    </div>
                </div>
            </div>

            <!-- Tabla de resultados - ancho completo -->
            <div class="tabla-container">
                <div class="tabla-header">
                    <h3>📊 Resultados del Historial</h3>
                    <div class="tabla-controles">
                        <span id="contador_resultados" class="contador-resultados">Mostrando 0 resultados</span>
                        <button type="button" id="btn_actualizar" class="btn btn-outline">
                            🔄 Actualizar
                        </button>
                    </div>
                </div>
                
                <div class="tabla-responsive" id="contenedor_tabla">
                    <div class="loading-spinner" id="loading_spinner" style="display: none;">
                        <div class="spinner"></div>
                        <p>Cargando historial...</p>
                    </div>
                    
                    <table id="tabla_historial" class="tabla-datos" style="display: none;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Activo</th>
                                <th>Número Serial</th>
                                <th>Persona Asignada</th>
                                <th>Área/Empresa</th>
                                <th>Fecha Asignación</th>
                                <th>Fecha Retorno</th>
                                <th>Duración</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_body">
                            <!-- Los datos se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                    
                    <div id="sin_resultados" class="sin-resultados" style="display: none;">
                        <div class="sin-resultados-icono">📭</div>
                        <h3>No se encontraron resultados</h3>
                        <p>Intenta ajustar los filtros de búsqueda</p>
                    </div>
                </div>
                
                <!-- Paginación -->
                <div class="paginacion-container" id="paginacion_container" style="display: none;">
                    <div class="paginacion-info">
                        <span id="info_paginacion">Página 1 de 1</span>
                    </div>
                    <div class="paginacion-controles">
                        <button type="button" id="btn_primera" class="btn-paginacion" disabled>⏮️</button>
                        <button type="button" id="btn_anterior" class="btn-paginacion" disabled>⏪</button>
                        <span id="pagina_actual" class="pagina-actual">1</span>
                        <button type="button" id="btn_siguiente" class="btn-paginacion" disabled>⏩</button>
                        <button type="button" id="btn_ultima" class="btn-paginacion" disabled>⏭️</button>
                    </div>
                    <div class="paginacion-tamaño">
                        <label for="registros_por_pagina">Mostrar:</label>
                        <select id="registros_por_pagina">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>registros</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

<!-- Modal para ver detalles de asignación -->
<div id="modalDetalleAsignacion" class="modal" style="display:none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>📋 Detalle de Asignación</h3>
            <span class="modal-close" id="cerrarModalDetalle">&times;</span>
        </div>
        <div class="modal-body" id="contenido_detalle_asignacion">
            <!-- El contenido se cargará dinámicamente -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cerrarModalDetalleBtn">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal para generar reporte -->
<div id="modalReporte" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📄 Generar Reporte PDF</h3>
            <span class="modal-close" id="cerrarModalReporte">&times;</span>
        </div>
        <div class="modal-body">
            <form id="form_reporte">
                <div class="form-group">
                    <label for="tipo_reporte">Tipo de Reporte:</label>
                    <select id="tipo_reporte" required>
                        <option value="">Seleccione...</option>
                        <option value="completo">📋 Reporte </option>
                        <option value="detallado">📄 Reporte Detallado</option>
                    </select>
                </div>
                <div class="form-group">
                    <p style="color: #6c757d; font-size: 13px; margin: 10px 0;">
                        <br>• <strong>Completo:</strong> Información visualizada en la vista actual 
                        <br>• <strong>Detallado:</strong> Información completa de cada asignación incluyendo todos los detalles
                    </p>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelarReporte">Cancelar</button>
            <button type="button" class="btn btn-primary" id="generarReporte">📄 Generar PDF</button>
        </div>
    </div>
</div>

<!-- Modal para gestionar archivos PDF -->
<div id="modalArchivos" class="modal" style="display:none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>📁 Gestión de Archivos PDF Generados</h3>
            <span class="modal-close" id="cerrarModalArchivos">&times;</span>
        </div>
        <div class="modal-body">
            <div class="archivos-container">
                <div class="archivos-header">
                    <p>📂 Ubicación: <code>pdf/historial_asignaciones/</code></p>
                    <button type="button" id="btn_actualizar_archivos" class="btn btn-outline btn-sm">
                        🔄 Actualizar Lista
                    </button>
                </div>
                
                <div id="loading_archivos" class="loading-spinner" style="display: none;">
                    <div class="spinner"></div>
                    <p>Cargando archivos...</p>
                </div>
                
                <div id="lista_archivos" class="archivos-lista">
                    <!-- Los archivos se cargarán aquí dinámicamente -->
                </div>
                
                <div id="sin_archivos" class="sin-resultados" style="display: none;">
                    <div class="sin-resultados-icono">📭</div>
                    <h3>No hay archivos PDF</h3>
                    <p>Aún no se han generado reportes PDF</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cerrarModalArchivosBtn">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar archivo -->
<div id="modalConfirmarEliminar" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>⚠️ Confirmar Eliminación</h3>
            <span class="modal-close" id="cerrarModalConfirmar">&times;</span>
        </div>
        <div class="modal-body">
            <p>¿Estás seguro de que deseas eliminar este archivo PDF?</p>
            <p><strong id="nombre_archivo_eliminar">archivo.pdf</strong></p>
            <p style="color: #dc3545; font-size: 14px; margin-top: 15px;">
                ⚠️ Esta acción no se puede deshacer.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelarEliminar">Cancelar</button>
            <button type="button" class="btn btn-danger" id="confirmarEliminar">🗑️ Eliminar</button>
        </div>
    </div>
</div>

    <!-- Scripts -->
    <script src="../../js/admin/crud_historial_asignacion.js"></script>
    <script src="../../js/admin/sidebar.js"></script>
</body>
</html>
