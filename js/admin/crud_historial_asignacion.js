document.addEventListener("DOMContentLoaded", function () {
    console.log("🚀 Iniciando sistema de historial de asignaciones...");
    
    // Variables globales
    let paginaActual = 1;
    let registrosPorPagina = 25;
    let totalPaginas = 1;
    let filtrosActivos = {};
    let datosActuales = [];
    let archivoAEliminar = null;
    
    // Elementos del DOM
    const elementos = {
        // Filtros
        buscarSerial: document.getElementById("buscar_numero_serial"),
        buscarActivo: document.getElementById("buscar_nombre_activo"),
        buscarPersona: document.getElementById("buscar_persona"),
        filtroTipoActivo: document.getElementById("filtro_tipo_activo"),
        filtroEstado: document.getElementById("filtro_estado_asignacion"),
        filtroFechaDesde: document.getElementById("filtro_fecha_desde"),
        filtroFechaHasta: document.getElementById("filtro_fecha_hasta"),
        
        // Botones de control
        btnBuscar: document.getElementById("btn_buscar"),
        btnLimpiar: document.getElementById("btn_limpiar"),
        btnExportar: document.getElementById("btn_exportar"),
        btnActualizar: document.getElementById("btn_actualizar"),
        btnArchivos: document.getElementById("btn_archivos"),
        
        // Estadísticas
        totalAsignaciones: document.getElementById("total_asignaciones"),
        asignacionesActivas: document.getElementById("asignaciones_activas"),
        asignacionesRetornadas: document.getElementById("asignaciones_retornadas"),
        promedioDias: document.getElementById("promedio_dias"),
        
        // Tabla y paginación
        tablaHistorial: document.getElementById("tabla_historial"),
        tablaBody: document.getElementById("tabla_body"),
        loadingSpinner: document.getElementById("loading_spinner"),
        sinResultados: document.getElementById("sin_resultados"),
        contadorResultados: document.getElementById("contador_resultados"),
        
        // Paginación
        paginacionContainer: document.getElementById("paginacion_container"),
        btnPrimera: document.getElementById("btn_primera"),
        btnAnterior: document.getElementById("btn_anterior"),
        btnSiguiente: document.getElementById("btn_siguiente"),
        btnUltima: document.getElementById("btn_ultima"),
        paginaActualSpan: document.getElementById("pagina_actual"),
        infoPaginacion: document.getElementById("info_paginacion"),
        selectRegistrosPorPagina: document.getElementById("registros_por_pagina"),
        
        // Modales
        modalDetalle: document.getElementById("modalDetalleAsignacion"),
        modalReporte: document.getElementById("modalReporte"),
        modalArchivos: document.getElementById("modalArchivos"),
        modalConfirmarEliminar: document.getElementById("modalConfirmarEliminar"),
        contenidoDetalle: document.getElementById("contenido_detalle_asignacion")
    };
    
    // Configurar event listeners
    configurarEventListeners();
    
    // Cargar datos iniciales
    cargarDatosIniciales();
    
    function configurarEventListeners() {
        console.log("⚙️ Configurando event listeners...");
        
        // Botones de control principal
        if (elementos.btnBuscar) {
            elementos.btnBuscar.addEventListener("click", ejecutarBusqueda);
        }
        
        if (elementos.btnLimpiar) {
            elementos.btnLimpiar.addEventListener("click", limpiarFiltros);
        }
        
        if (elementos.btnActualizar) {
            elementos.btnActualizar.addEventListener("click", actualizarDatos);
        }
        
        if (elementos.btnExportar) {
            elementos.btnExportar.addEventListener("click", abrirModalReporte);
        }
        
        if (elementos.btnArchivos) {
            elementos.btnArchivos.addEventListener("click", abrirModalArchivos);
        }
        
        // Enter en campos de búsqueda
        [elementos.buscarSerial, elementos.buscarActivo, elementos.buscarPersona].forEach(campo => {
            if (campo) {
                campo.addEventListener("keypress", function(e) {
                    if (e.key === "Enter") {
                        ejecutarBusqueda();
                    }
                });
            }
        });
        
        // Cambios en selectores
        if (elementos.selectRegistrosPorPagina) {
            elementos.selectRegistrosPorPagina.addEventListener("change", function() {
                registrosPorPagina = parseInt(this.value);
                paginaActual = 1;
                cargarHistorial();
            });
        }
        
        // Botones de paginación
        if (elementos.btnPrimera) {
            elementos.btnPrimera.addEventListener("click", () => irAPagina(1));
        }
        if (elementos.btnAnterior) {
            elementos.btnAnterior.addEventListener("click", () => irAPagina(paginaActual - 1));
        }
        if (elementos.btnSiguiente) {
            elementos.btnSiguiente.addEventListener("click", () => irAPagina(paginaActual + 1));
        }
        if (elementos.btnUltima) {
            elementos.btnUltima.addEventListener("click", () => irAPagina(totalPaginas));
        }
        
        // Modales
        configurarModales();
        
        console.log("✅ Event listeners configurados");
    }
    
    function configurarModales() {
        // Modal de detalle
        const cerrarDetalle = document.getElementById("cerrarModalDetalle");
        const cerrarDetalleBtn = document.getElementById("cerrarModalDetalleBtn");
        
        if (cerrarDetalle) {
            cerrarDetalle.addEventListener("click", () => cerrarModal(elementos.modalDetalle));
        }
        if (cerrarDetalleBtn) {
            cerrarDetalleBtn.addEventListener("click", () => cerrarModal(elementos.modalDetalle));
        }
        
        // Modal de reporte
        const cerrarReporte = document.getElementById("cerrarModalReporte");
        const cancelarReporte = document.getElementById("cancelarReporte");
        const generarReporte = document.getElementById("generarReporte");
        const tipoReporte = document.getElementById("tipo_reporte");
        
        if (cerrarReporte) {
            cerrarReporte.addEventListener("click", () => cerrarModal(elementos.modalReporte));
        }
        if (cancelarReporte) {
            cancelarReporte.addEventListener("click", () => cerrarModal(elementos.modalReporte));
        }
        if (generarReporte) {
            generarReporte.addEventListener("click", procesarGeneracionReporte);
        }
        
        // Modal de archivos PDF
        const cerrarArchivos = document.getElementById("cerrarModalArchivos");
        const cerrarArchivosBtn = document.getElementById("cerrarModalArchivosBtn");
        const btnActualizarArchivos = document.getElementById("btn_actualizar_archivos");
        
        if (cerrarArchivos) {
            cerrarArchivos.addEventListener("click", () => cerrarModal(elementos.modalArchivos));
        }
        if (cerrarArchivosBtn) {
            cerrarArchivosBtn.addEventListener("click", () => cerrarModal(elementos.modalArchivos));
        }
        if (btnActualizarArchivos) {
            btnActualizarArchivos.addEventListener("click", cargarListaArchivos);
        }
        
        // Modal de confirmación de eliminación
        const cerrarConfirmar = document.getElementById("cerrarModalConfirmar");
        const cancelarEliminar = document.getElementById("cancelarEliminar");
        const confirmarEliminar = document.getElementById("confirmarEliminar");
        
        if (cerrarConfirmar) {
            cerrarConfirmar.addEventListener("click", () => cerrarModal(elementos.modalConfirmarEliminar));
        }
        if (cancelarEliminar) {
            cancelarEliminar.addEventListener("click", () => cerrarModal(elementos.modalConfirmarEliminar));
        }
        if (confirmarEliminar) {
            confirmarEliminar.addEventListener("click", ejecutarEliminacion);
        }
        
        // Mostrar/ocultar opciones detalladas según el tipo de reporte
        if (tipoReporte) {
            tipoReporte.addEventListener("change", function() {
                const opcionesDetallado = document.getElementById("opciones_detallado");
                if (opcionesDetallado) {
                    // Mostrar opciones para todos los tipos de reporte
                    opcionesDetallado.style.display = this.value ? "block" : "none";
                }
            });
        }
        
        // Cerrar modales con Escape
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                if (elementos.modalDetalle && elementos.modalDetalle.style.display === "block") {
                    cerrarModal(elementos.modalDetalle);
                }
                if (elementos.modalReporte && elementos.modalReporte.style.display === "block") {
                    cerrarModal(elementos.modalReporte);
                }
                if (elementos.modalArchivos && elementos.modalArchivos.style.display === "block") {
                    cerrarModal(elementos.modalArchivos);
                }
                if (elementos.modalConfirmarEliminar && elementos.modalConfirmarEliminar.style.display === "block") {
                    cerrarModal(elementos.modalConfirmarEliminar);
                }
            }
        });
    }
    
    function cargarDatosIniciales() {
        console.log("📊 Cargando datos iniciales...");
        cargarEstadisticas();
        cargarHistorial();
    }
    
    function ejecutarBusqueda() {
        console.log("🔍 Ejecutando búsqueda...");
        
        // Obtener filtros actuales
        filtrosActivos = obtenerFiltrosActuales();
        paginaActual = 1; // Resetear a primera página
        
        // Cargar datos con filtros
        cargarEstadisticas();
        cargarHistorial();
    }
    
    function limpiarFiltros() {
        console.log("🧹 Limpiando filtros...");
        
        // Limpiar campos de entrada
        if (elementos.buscarSerial) elementos.buscarSerial.value = "";
        if (elementos.buscarActivo) elementos.buscarActivo.value = "";
        if (elementos.buscarPersona) elementos.buscarPersona.value = "";
        if (elementos.filtroTipoActivo) elementos.filtroTipoActivo.value = "";
        if (elementos.filtroEstado) elementos.filtroEstado.value = "";
        if (elementos.filtroFechaDesde) elementos.filtroFechaDesde.value = "";
        if (elementos.filtroFechaHasta) elementos.filtroFechaHasta.value = "";
        
        // Resetear variables
        filtrosActivos = {};
        paginaActual = 1;
        
        // Recargar datos
        cargarEstadisticas();
        cargarHistorial();
    }
    
    function actualizarDatos() {
        console.log("🔄 Actualizando datos...");
        cargarEstadisticas();
        cargarHistorial();
    }
    
    function obtenerFiltrosActuales() {
        const filtros = {};
        
        if (elementos.buscarSerial && elementos.buscarSerial.value.trim()) {
            filtros.numero_serial = elementos.buscarSerial.value.trim();
        }
        if (elementos.buscarActivo && elementos.buscarActivo.value.trim()) {
            filtros.nombre_activo = elementos.buscarActivo.value.trim();
        }
        if (elementos.buscarPersona && elementos.buscarPersona.value.trim()) {
            filtros.persona = elementos.buscarPersona.value.trim();
        }
        if (elementos.filtroTipoActivo && elementos.filtroTipoActivo.value) {
            filtros.tipo_activo = elementos.filtroTipoActivo.value;
        }
        if (elementos.filtroEstado && elementos.filtroEstado.value) {
            filtros.estado_asignacion = elementos.filtroEstado.value;
        }
        if (elementos.filtroFechaDesde && elementos.filtroFechaDesde.value) {
            filtros.fecha_desde = elementos.filtroFechaDesde.value;
        }
        if (elementos.filtroFechaHasta && elementos.filtroFechaHasta.value) {
            filtros.fecha_hasta = elementos.filtroFechaHasta.value;
        }
        
        return filtros;
    }
    
    function cargarEstadisticas() {
        console.log("📈 Cargando estadísticas...");
        
        const params = new URLSearchParams(filtrosActivos);
        
        fetch(`../controllers/procesar_historial_asignacion.php?accion=obtener_estadisticas&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                actualizarEstadisticas(data);
            })
            .catch(error => {
                console.error("❌ Error cargando estadísticas:", error);
                mostrarErrorEstadisticas();
            });
    }
    
    function actualizarEstadisticas(data) {
        if (elementos.totalAsignaciones) {
            elementos.totalAsignaciones.textContent = data.total_asignaciones || 0;
        }
        if (elementos.asignacionesActivas) {
            elementos.asignacionesActivas.textContent = data.asignaciones_activas || 0;
        }
        if (elementos.asignacionesRetornadas) {
            elementos.asignacionesRetornadas.textContent = data.asignaciones_retornadas || 0;
        }
        if (elementos.promedioDias) {
            elementos.promedioDias.textContent = `${data.promedio_dias || 0} días`;
        }
        
        console.log("✅ Estadísticas actualizadas");
    }
    
    function mostrarErrorEstadisticas() {
        if (elementos.totalAsignaciones) elementos.totalAsignaciones.textContent = "Error";
        if (elementos.asignacionesActivas) elementos.asignacionesActivas.textContent = "Error";
        if (elementos.asignacionesRetornadas) elementos.asignacionesRetornadas.textContent = "Error";
        if (elementos.promedioDias) elementos.promedioDias.textContent = "Error";
    }
    
    function cargarHistorial() {
        console.log("📋 Cargando historial...");
        
        mostrarLoading(true);
        
        const params = new URLSearchParams({
            ...filtrosActivos,
            pagina: paginaActual,
            limite: registrosPorPagina
        });
        
        fetch(`../controllers/procesar_historial_asignacion.php?accion=obtener_historial&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                datosActuales = data.datos;
                totalPaginas = data.total_paginas;
                
                mostrarDatosEnTabla(data.datos);
                actualizarPaginacion(data);
                actualizarContadorResultados(data.total);
                
                mostrarLoading(false);
            })
            .catch(error => {
                console.error("❌ Error cargando historial:", error);
                mostrarError("Error al cargar el historial de asignaciones");
                mostrarLoading(false);
            });
    }
    
    function mostrarLoading(mostrar) {
        if (elementos.loadingSpinner) {
            elementos.loadingSpinner.style.display = mostrar ? "flex" : "none";
        }
        if (elementos.tablaHistorial) {
            elementos.tablaHistorial.style.display = mostrar ? "none" : "table";
        }
        if (elementos.sinResultados) {
            elementos.sinResultados.style.display = "none";
        }
    }
    
    function mostrarDatosEnTabla(datos) {
        if (!elementos.tablaBody) return;
        
        elementos.tablaBody.innerHTML = "";
        
        if (datos.length === 0) {
            mostrarSinResultados();
            return;
        }
        
        datos.forEach(asignacion => {
            const fila = crearFilaTabla(asignacion);
            elementos.tablaBody.appendChild(fila);
        });
        
        elementos.tablaHistorial.style.display = "table";
        elementos.sinResultados.style.display = "none";
    }
    
    function crearFilaTabla(asignacion) {
        const fila = document.createElement("tr");
        
        // Determinar clase CSS según estado
        const claseEstado = asignacion.estado_asignacion === "Activo" ? "estado-asignado" : "estado-disponible";
        fila.className = claseEstado;
        
        // Formatear duración
        const duracion = formatearDuracion(asignacion.dias_asignacion, asignacion.estado_asignacion);
        
        fila.innerHTML = `
            <td>${asignacion.id_asignacion}</td>
            <td>${asignacion.nombre_activo || 'Sin nombre'}</td>
            <td>${asignacion.numero_serial || 'Sin serial'}</td>
            <td>${asignacion.persona_nombre}</td>
            <td>${asignacion.area_nombre || 'Sin área'} / ${asignacion.empresa_nombre || 'Sin empresa'}</td>
            <td>${asignacion.fecha_asignacion_formato}</td>
            <td>${asignacion.fecha_retorno_formato || 'Pendiente'}</td>
            <td>${duracion}</td>
            <td>
                <span class="estado-badge ${asignacion.estado_asignacion.toLowerCase()}">
                    ${asignacion.estado_asignacion}
                </span>
            </td>
            <td>
                <button type="button" class="btn-accion btn-detalle" onclick="verDetalleAsignacion(${asignacion.id_asignacion})">
                    Ver Detalle
                </button>
            </td>
        `;
        
        return fila;
    }
    
    function formatearDuracion(dias, estado) {
        if (!dias || dias < 0) return "No calculable";
        
        let texto = "";
        if (dias === 0) {
            texto = "Mismo día";
        } else if (dias === 1) {
            texto = "1 día";
        } else if (dias < 30) {
            texto = `${dias} días`;
        } else if (dias < 365) {
            const meses = Math.floor(dias / 30);
            const diasRestantes = dias % 30;
            texto = diasRestantes === 0 ? 
                `${meses} ${meses === 1 ? 'mes' : 'meses'}` : 
                `${meses} ${meses === 1 ? 'mes' : 'meses'} y ${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`;
        } else {
            const años = Math.floor(dias / 365);
            const diasRestantes = dias % 365;
            texto = diasRestantes === 0 ? 
                `${años} ${años === 1 ? 'año' : 'años'}` : 
                `${años} ${años === 1 ? 'año' : 'años'} y ${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`;
        }
        
        if (estado === "Activo") {
            texto += " (en curso)";
        }
        
        return texto;
    }
    
    function mostrarSinResultados() {
        if (elementos.tablaHistorial) {
            elementos.tablaHistorial.style.display = "none";
        }
        if (elementos.sinResultados) {
            elementos.sinResultados.style.display = "block";
        }
    }
    
    function actualizarContadorResultados(total) {
        if (elementos.contadorResultados) {
            elementos.contadorResultados.textContent = `Mostrando ${total} resultado${total !== 1 ? 's' : ''}`;
        }
    }
    
    function actualizarPaginacion(data) {
        if (!elementos.paginacionContainer) return;
        
        totalPaginas = data.total_paginas;
        
        // Mostrar/ocultar contenedor de paginación
        if (data.total <= registrosPorPagina) {
            elementos.paginacionContainer.style.display = "none";
            return;
        }
        
        elementos.paginacionContainer.style.display = "flex";
        
        // Actualizar información
        if (elementos.infoPaginacion) {
            elementos.infoPaginacion.textContent = `Página ${paginaActual} de ${totalPaginas}`;
        }
        
        if (elementos.paginaActualSpan) {
            elementos.paginaActualSpan.textContent = paginaActual;
        }
        
        // Actualizar estado de botones
        if (elementos.btnPrimera) {
            elementos.btnPrimera.disabled = paginaActual <= 1;
        }
        if (elementos.btnAnterior) {
            elementos.btnAnterior.disabled = paginaActual <= 1;
        }
        if (elementos.btnSiguiente) {
            elementos.btnSiguiente.disabled = paginaActual >= totalPaginas;
        }
        if (elementos.btnUltima) {
            elementos.btnUltima.disabled = paginaActual >= totalPaginas;
        }
    }
    
    function irAPagina(pagina) {
        if (pagina < 1 || pagina > totalPaginas || pagina === paginaActual) {
            return;
        }
        
        paginaActual = pagina;
        cargarHistorial();
    }
    
    // Función global para ver detalle (llamada desde HTML)
    window.verDetalleAsignacion = function(idAsignacion) {
        console.log(`👀 Abriendo detalle de asignación ${idAsignacion}`);
        
        fetch(`../controllers/procesar_historial_asignacion.php?accion=obtener_detalle&id_asignacion=${idAsignacion}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                mostrarDetalleEnModal(data);
            })
            .catch(error => {
                console.error("❌ Error cargando detalle:", error);
                alert("Error al cargar el detalle de la asignación");
            });
    };
    
    function mostrarDetalleEnModal(detalle) {
        if (!elementos.contenidoDetalle || !elementos.modalDetalle) return;
        
        const duracion = formatearDuracion(detalle.dias_asignacion, detalle.estado_asignacion);
        
        elementos.contenidoDetalle.innerHTML = `
            <div class="detalle-grid">
                <div class="detalle-seccion">
                    <h4>👤 Información de la Persona</h4>
                    <div class="detalle-item">
                        <span class="detalle-label">Nombre:</span>
                        <span class="detalle-valor">${detalle.persona_nombre} ${detalle.persona_apellido}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Email:</span>
                        <span class="detalle-valor">${detalle.email || 'No especificado'}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Teléfono:</span>
                        <span class="detalle-valor">${detalle.telefono || 'No especificado'}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Localidad:</span>
                        <span class="detalle-valor">${detalle.localidad_nombre || 'No especificado'}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Área:</span>
                        <span class="detalle-valor">${detalle.area_nombre || 'No especificado'}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Empresa:</span>
                        <span class="detalle-valor">${detalle.empresa_nombre || 'No especificado'}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Jefe Inmediato:</span>
                        <span class="detalle-valor">${detalle.jefe_inmediato || 'No especificado'}</span>
                    </div>
                </div>
                
                <div class="detalle-seccion">
                    <h4>💻 Información del Activo</h4>
                    <div class="detalle-item">
                        <span class="detalle-label">Tipo:</span>
                        <span class="detalle-valor">${detalle.tipo_activo}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Nombre:</span>
                        <span class="detalle-valor">${detalle.nombre_activo}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Marca:</span>
                        <span class="detalle-valor">${detalle.marca_activo || 'No especificado'}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Modelo:</span>
                        <span class="detalle-valor">${detalle.modelo_activo || 'No especificado'}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Número Serial:</span>
                        <span class="detalle-valor">${detalle.numero_serial || 'No especificado'}</span>
                    </div>
                </div>
                
                <div class="detalle-seccion">
                    <h4>📅 Información de la Asignación</h4>
                    <div class="detalle-item">
                        <span class="detalle-label">ID Asignación:</span>
                        <span class="detalle-valor">${detalle.id_asignacion}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Fecha Asignación:</span>
                        <span class="detalle-valor">${detalle.fecha_asignacion_formato}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Fecha Retorno:</span>
                        <span class="detalle-valor">${detalle.fecha_retorno_formato || 'Pendiente'}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Duración:</span>
                        <span class="detalle-valor">${duracion}</span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Estado:</span>
                        <span class="detalle-valor">
                            <span class="estado-badge ${detalle.estado_asignacion.toLowerCase()}">
                                ${detalle.estado_asignacion}
                            </span>
                        </span>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label">Usuario que Asignó:</span>
                        <span class="detalle-valor">${detalle.usuario_asigno || 'No especificado'}</span>
                    </div>
                </div>
                
                <div class="detalle-seccion">
                    <h4>📝 Observaciones</h4>
                    <div class="detalle-item">
                        <span class="detalle-valor">${detalle.observaciones || 'Sin observaciones'}</span>
                    </div>
                </div>
            </div>
        `;
        
        elementos.modalDetalle.style.display = "block";
    }
    
    function abrirModalReporte() {
        console.log("📄 Abriendo modal de reporte...");
        if (elementos.modalReporte) {
            elementos.modalReporte.style.display = "block";
        }
    }
    
    function abrirModalArchivos() {
        console.log("📁 Abriendo modal de archivos PDF...");
        if (elementos.modalArchivos) {
            elementos.modalArchivos.style.display = "block";
            cargarListaArchivos();
        }
    }
    
    function cargarListaArchivos() {
        console.log("📂 Cargando lista de archivos PDF...");
        
        const loadingArchivos = document.getElementById("loading_archivos");
        const listaArchivos = document.getElementById("lista_archivos");
        const sinArchivos = document.getElementById("sin_archivos");
        
        // Mostrar loading
        if (loadingArchivos) loadingArchivos.style.display = "flex";
        if (listaArchivos) listaArchivos.style.display = "none";
        if (sinArchivos) sinArchivos.style.display = "none";

        // Primero verificar si podemos acceder al endpoint
        fetch('../controllers/pdf_historial_asignacion/gestionar_archivos_pdf.php?accion=listar', {
            method: 'GET',
            credentials: 'same-origin', // Asegurar que se envían las cookies de sesión
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                console.log('Response headers:', response.headers.get('content-type'));
                return response.text(); // Primero obtener como texto para debuggear
            })
            .then(text => {
                console.log('Response text completo:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Data parseada:', data);
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    mostrarListaArchivos(data.archivos);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Texto que causó el error:', text);
                    throw new Error('Respuesta no válida del servidor: ' + text.substring(0, 200));
                }
                
                // Ocultar loading
                if (loadingArchivos) loadingArchivos.style.display = "none";
            })
            .catch(error => {
                console.error("❌ Error cargando archivos:", error);
                if (loadingArchivos) loadingArchivos.style.display = "none";
                if (sinArchivos) {
                    sinArchivos.style.display = "block";
                    sinArchivos.innerHTML = `
                        <div class="sin-resultados-icono">❌</div>
                        <h3>Error al cargar archivos</h3>
                        <p>${error.message}</p>
                        <p><small>Verifica que tengas permisos de administrador y que tu sesión esté activa.</small></p>
                    `;
                }
            });
    }
    
    function mostrarListaArchivos(archivos) {
        const listaArchivos = document.getElementById("lista_archivos");
        const sinArchivos = document.getElementById("sin_archivos");
        
        if (!archivos || archivos.length === 0) {
            if (listaArchivos) listaArchivos.style.display = "none";
            if (sinArchivos) sinArchivos.style.display = "block";
            return;
        }
        
        if (!listaArchivos) return;
        
        listaArchivos.innerHTML = "";
        listaArchivos.style.display = "block";
        if (sinArchivos) sinArchivos.style.display = "none";
        
        // Agrupar archivos por carpeta
        const archivosPorCarpeta = {};
        archivos.forEach(archivo => {
            const carpeta = archivo.carpeta || 'Sin categoría';
            if (!archivosPorCarpeta[carpeta]) {
                archivosPorCarpeta[carpeta] = [];
            }
            archivosPorCarpeta[carpeta].push(archivo);
        });
        
        // Mostrar archivos agrupados
        Object.keys(archivosPorCarpeta).sort().forEach(carpeta => {
            const carpetaDiv = document.createElement('div');
            carpetaDiv.className = 'archivo-carpeta';
            carpetaDiv.innerHTML = `
                <h4 style="margin: 15px 0 10px 0; color: #007bff; border-bottom: 1px solid #dee2e6; padding-bottom: 5px;">
                    📂 ${carpeta}
                </h4>
            `;
            
            const archivosDiv = document.createElement('div');
            archivosDiv.className = 'archivos-grid';
            archivosDiv.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 10px; margin-bottom: 20px;';
            
            archivosPorCarpeta[carpeta].forEach(archivo => {
                const archivoCard = document.createElement('div');
                archivoCard.className = 'archivo-card';
                archivoCard.style.cssText = 'border: 1px solid #dee2e6; border-radius: 5px; padding: 12px; background: #f8f9fa;';
                
                const fechaFormateada = new Date(archivo.fecha_modificacion).toLocaleString('es-PE');
                const tamañoFormateado = formatearTamaño(archivo.tamaño);
                
                archivoCard.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: bold; font-size: 14px; margin-bottom: 5px; word-break: break-word;">
                                📄 ${archivo.nombre}
                            </div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 3px;">
                                📅 ${fechaFormateada}
                            </div>
                            <div style="font-size: 12px; color: #666;">
                                📊 ${tamañoFormateado}
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px; margin-left: 10px;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="visualizarArchivo('${archivo.ruta_relativa}')" style="font-size: 11px; padding: 4px 8px;">
                                 Ver
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmarEliminacion('${archivo.ruta_relativa}', '${archivo.nombre}')" style="font-size: 11px; padding: 4px 8px;">
                                 Eliminar
                            </button>
                        </div>
                    </div>
                `;
                
                archivosDiv.appendChild(archivoCard);
            });
            
            carpetaDiv.appendChild(archivosDiv);
            listaArchivos.appendChild(carpetaDiv);
        });
    }
    
    function formatearTamaño(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Funciones globales para los botones
    window.visualizarArchivo = function(rutaRelativa) {
        console.log(" Visualizando archivo:", rutaRelativa);
        const url = `../../../${rutaRelativa}`;
        window.open(url, '_blank');
    };
    
    window.confirmarEliminacion = function(rutaRelativa, nombreArchivo) {
        console.log(" Confirmando eliminación:", nombreArchivo);
        archivoAEliminar = rutaRelativa;
        
        const nombreArchivoSpan = document.getElementById("nombre_archivo_eliminar");
        if (nombreArchivoSpan) {
            nombreArchivoSpan.textContent = nombreArchivo;
        }
        
        if (elementos.modalConfirmarEliminar) {
            elementos.modalConfirmarEliminar.style.display = "block";
        }
    };
    
    function procesarGeneracionReporte() {
        const tipoReporte = document.getElementById("tipo_reporte")?.value;
        const incluirFiltros = document.getElementById("incluir_filtros")?.checked ?? true;
        const incluirFechaGeneracion = document.getElementById("incluir_fecha_generacion")?.checked ?? true;
        const incluirUsuario = document.getElementById("incluir_usuario")?.checked ?? true;
        
        if (!tipoReporte) {
            alert("Por favor seleccione el tipo de reporte");
            return;
        }
        
        console.log(" Generando reporte PDF:", { tipoReporte, incluirFiltros });
        
        // Mostrar mensaje de procesamiento
        const btnGenerar = document.getElementById("generarReporte");
        const textoOriginal = btnGenerar.textContent;
        btnGenerar.textContent = "Generando PDF...";
        btnGenerar.disabled = true;
        
        // Crear formulario para envío POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controllers/pdf_historial_asignacion/generar_pdf_historial_asignacion.php';
        form.target = '_blank';
        
        // Agregar campos del formulario
        const campos = {
            tipo_reporte: tipoReporte,
            incluir_filtros: incluirFiltros ? '1' : '0',
            incluir_fecha_generacion: incluirFechaGeneracion ? '1' : '0',
            incluir_usuario: incluirUsuario ? '1' : '0',
            ...filtrosActivos // Incluir todos los filtros actuales
        };
        
        Object.keys(campos).forEach(key => {
            if (campos[key] !== undefined && campos[key] !== '') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = campos[key];
                form.appendChild(input);
            }
        });
        
        // Enviar formulario
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        // Cerrar modal después de un breve retraso
        setTimeout(() => {
            cerrarModal(elementos.modalReporte);
        }, 1000);
        
        // Restaurar botón
        setTimeout(() => {
            btnGenerar.textContent = textoOriginal;
            btnGenerar.disabled = false;
        }, 2000);
    }
    
    function ejecutarEliminacion() {
        if (!archivoAEliminar) return;
        
        console.log("🗑️ Eliminando archivo:", archivoAEliminar);
        
        const btnConfirmar = document.getElementById("confirmarEliminar");
        const textoOriginal = btnConfirmar.textContent;
        btnConfirmar.textContent = "Eliminando...";
        btnConfirmar.disabled = true;
        
        fetch('../controllers/pdf_historial_asignacion/gestionar_archivos_pdf.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                accion: 'eliminar',
                ruta_archivo: archivoAEliminar
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log("✅ Archivo eliminado exitosamente");
                cerrarModal(elementos.modalConfirmarEliminar);
                cargarListaArchivos(); // Recargar la lista
                alert("Archivo eliminado exitosamente");
            } else {
                throw new Error(data.message || 'Error al eliminar el archivo');
            }
        })
        .catch(error => {
            console.error("❌ Error eliminando archivo:", error);
            alert("Error al eliminar el archivo: " + error.message);
        })
        .finally(() => {
            btnConfirmar.textContent = textoOriginal;
            btnConfirmar.disabled = false;
            archivoAEliminar = null;
        });
    }
    
    function cerrarModal(modal) {
        if (modal) {
            modal.style.display = "none";
        }
    }
    
    function mostrarError(mensaje) {
        console.error("❌", mensaje);
        alert(mensaje);
    }
    
    console.log("✅ Sistema de historial de asignaciones cargado correctamente");
});




