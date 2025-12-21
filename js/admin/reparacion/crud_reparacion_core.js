// crud_reparacion_core.js
// 📌 Funciones principales de CRUD de Reparaciones

import { cargarCatalogos } from "./catalogos.js";
import { showLoading, mostrarError, formatearFecha, getBadgeColor, getEstadoClass } from "./utils.js";

// ============================
// Inicialización del sistema
// ============================
export async function inicializarSistema() {
    try {
        console.log("Iniciando sistema...");

        showLoading(true);

        await cargarCatalogos();
        console.log("Catálogos cargados correctamente");

        await cargarReparaciones();
        console.log("Reparaciones cargadas correctamente");

        showLoading(false);
        console.log("✅ Sistema inicializado correctamente");
    } catch (error) {
        console.error("Error en inicialización:", error);
        showLoading(false);
        mostrarError("Error al inicializar el sistema: " + error.message);
    }
}

// ============================
// Cargar todas las reparaciones
// ============================
export async function cargarReparaciones() {
    try {
        console.log("Cargando reparaciones...");
        
        const tbody = document.querySelector("#tablaReparaciones tbody");
        if (!tbody) {
            throw new Error("No se encontró la tabla de reparaciones");
        }

        // Mostrar indicador de carga
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Cargando reparaciones...</td></tr>';

        // Como las reparaciones ya están cargadas desde PHP, las tomamos directamente del DOM
        const filasExistentes = tbody.querySelectorAll('tr');
        
        // Si ya hay datos del servidor PHP, no necesitamos hacer otra consulta
        if (filasExistentes.length > 0 && !filasExistentes[0].textContent.includes('Cargando')) {
            console.log("Reparaciones ya cargadas desde el servidor PHP");
            configurarEventosTabla();
            return;
        }

        // Si llegamos aquí, intentamos cargar via AJAX como respaldo
        try {
            const response = await fetch("../controllers/procesar_reparacion.php?action=get_reparaciones");
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const text = await response.text();
            console.log("Respuesta del servidor:", text.substring(0, 200) + "...");

            if (!text.trim()) {
                // Si no hay respuesta AJAX, usar los datos del PHP inicial
                tbody.innerHTML = '';
                configurarEventosTabla();
                return;
            }

            const reparaciones = JSON.parse(text);
            
            if (reparaciones.success === false) {
                throw new Error(reparaciones.message || "Error del servidor");
            }

            tbody.innerHTML = "";

            if (Array.isArray(reparaciones)) {
                if (reparaciones.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #666; padding: 20px;">
                        No hay reparaciones registradas
                    </td></tr>`;
                } else {
                    reparaciones.forEach((reparacion) => {
                        const fila = crearFilaReparacion(reparacion);
                        tbody.appendChild(fila);
                    });
                }
            } else {
                throw new Error("Formato de datos incorrecto");
            }
            
        } catch (ajaxError) {
            console.warn("Error en carga AJAX, usando datos del servidor:", ajaxError.message);
            // Limpiar mensaje de carga y usar datos existentes
            tbody.innerHTML = '';
        }
        
        configurarEventosTabla();
        
    } catch (error) {
        console.error("Error cargando reparaciones:", error);
        const tbody = document.querySelector("#tablaReparaciones tbody");
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #dc3545; padding: 20px;">
                Error cargando reparaciones: ${error.message}
            </td></tr>`;
        }
    }
}

// ============================
// Configurar eventos de la tabla
// ============================
function configurarEventosTabla() {
    // Importar funciones de hardware dinámicamente para evitar dependencias circulares
    import("./hardware.js").then(hardwareModule => {
        // Configurar botones VER
        document.querySelectorAll(".btn-ver").forEach((btn) => {
            btn.addEventListener("click", () => {
                hardwareModule.verDetallesReparacion(btn);
            });
        });

        // Configurar botones HARDWARE
        document.querySelectorAll(".btn-hardware").forEach((btn) => {
            btn.addEventListener("click", () => {
                hardwareModule.gestionarCambiosHardware(btn.dataset.id, btn.dataset.activo);
            });
        });
    });

    // Configurar botones EDITAR
    document.querySelectorAll(".btn-editar").forEach((btn) => {
        btn.addEventListener("click", () => {
            editarReparacion(btn.dataset.id);
        });
    });
}

// ============================
// Crear fila de reparación en tabla
// ============================
function crearFilaReparacion(reparacion) {
    const tr = document.createElement('tr');
    tr.className = getEstadoClass(reparacion.nombre_estado);
    
    // CORREGIDO: Formatear fecha correctamente sin problemas de zona horaria
    let fechaFormateada = 'Sin fecha';
    let fechaParaEditar = '';
    
    if (reparacion.fecha) {
        try {
            if (typeof reparacion.fecha === 'object' && reparacion.fecha.date) {
                // Objeto DateTime de SQL Server
                const fechaStr = reparacion.fecha.date;
                const fechaObj = new Date(fechaStr);
                
                // Para mostrar: usar UTC para evitar cambio de zona horaria
                const dia = fechaObj.getUTCDate();
                const mes = fechaObj.getUTCMonth() + 1;
                const anio = fechaObj.getUTCFullYear();
                fechaFormateada = `${dia}/${mes}/${anio}`;
                
                // Para editar: formato ISO
                fechaParaEditar = fechaObj.toISOString().split('T')[0];
                
            } else if (typeof reparacion.fecha === 'string') {
                if (reparacion.fecha.includes('-')) {
                    // Formato YYYY-MM-DD
                    const [year, month, day] = reparacion.fecha.split('T')[0].split('-');
                    fechaFormateada = `${parseInt(day)}/${parseInt(month)}/${year}`;
                    fechaParaEditar = reparacion.fecha.split('T')[0];
                } else {
                    // Otro formato
                    const fechaObj = new Date(reparacion.fecha);
                    if (!isNaN(fechaObj.getTime())) {
                        const dia = fechaObj.getUTCDate();
                        const mes = fechaObj.getUTCMonth() + 1;
                        const anio = fechaObj.getUTCFullYear();
                        fechaFormateada = `${dia}/${mes}/${anio}`;
                        fechaParaEditar = fechaObj.toISOString().split('T')[0];
                    }
                }
            }
        } catch (e) {
            console.error("Error formateando fecha:", e, reparacion.fecha);
            fechaFormateada = 'Fecha inválida';
            fechaParaEditar = '';
        }
    }
    
    // CORREGIDO: Manejo específico del tiempo de inactividad para edición
    let tiempoParaEditar = '';
    if (reparacion.tiempo_inactividad !== null && reparacion.tiempo_inactividad !== undefined) {
        tiempoParaEditar = reparacion.tiempo_inactividad.toString();
    }
    
    console.log("DEBUG CREAR FILA: Tiempo de inactividad:", reparacion.tiempo_inactividad, "Para editar:", tiempoParaEditar);
    
    tr.innerHTML = `
        <td>${reparacion.id_reparacion}</td>
        <td>${reparacion.nombre_equipo} (${reparacion.tipo_activo})</td>
        <td>${fechaFormateada}</td>
        <td><span class="badge bg-${getBadgeColor(reparacion.nombre_estado)}">${reparacion.nombre_estado}</span></td>
        <td>${reparacion.nombre_lugar}</td>
        <td>${reparacion.costo ? 'S/ ' + parseFloat(reparacion.costo).toFixed(2) : '-'}</td>
        <td>
            <div class="acciones">
                <button type="button" class="btn-icon btn-ver"
                    data-id-reparacion="${reparacion.id_reparacion}"
                    data-fecha="${fechaFormateada}"
                    data-nombre-estado="${reparacion.nombre_estado || ''}"
                    data-nombre-lugar="${reparacion.nombre_lugar || ''}"
                    data-costo="${reparacion.costo || ''}"
                    data-tiempo-inactividad="${tiempoParaEditar}"
                    data-nombre-equipo="${reparacion.nombre_equipo || ''}"
                    data-tipo-activo="${reparacion.tipo_activo || ''}"
                    data-id-activo="${reparacion.id_activo || ''}"
                    data-persona-asignada="${reparacion.persona_asignada || ''}"
                    data-descripcion="${reparacion.descripcion || ''}">
                    <img src="../../img/ojo.png" alt="Ver">
                </button>
                
                <button type="button" class="btn-icon btn-editar"
                    data-id="${reparacion.id_reparacion}"
                    data-id-activo="${reparacion.id_activo}"
                    data-id-lugar="${reparacion.id_lugar_reparacion}"
                    data-id-estado="${reparacion.id_estado_reparacion}"
                    data-fecha="${fechaParaEditar}"
                    data-descripcion="${reparacion.descripcion || ''}"
                    data-costo="${reparacion.costo || ''}"
                    data-tiempo="${tiempoParaEditar}">
                    <img src="../../img/editar.png" alt="Editar">
                </button>
                                              
                <button type="button" class="btn-icon btn-hardware"
                    data-id="${reparacion.id_reparacion}"
                    data-activo="${reparacion.id_activo}"
                    title="Gestionar cambios de hardware">
                    <img src="../../img/hardware.png" alt="Hardware">
                </button>

                <form method="POST" action="../controllers/procesar_reparacion.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta reparación?');">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_reparacion" value="${reparacion.id_reparacion}">
                    <button type="submit" class="btn-icon">
                        <img src="../../img/eliminar.png" alt="Eliminar">
                    </button>
                </form>
            </div>
        </td>
    `;
    
    return tr;
}

// ============================
// Guardar reparación (crear o actualizar)
// ============================
export async function guardarReparacion() {
    console.log("Guardando reparación...");

    const form = document.getElementById("formReparacion");
    if (!form) {
        alert("No se encontró el formulario");
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const tiempoInactividad = document.getElementById("tiempo_inactividad").value;
    const costo = document.getElementById("costo").value;

    if (tiempoInactividad !== "" && parseInt(tiempoInactividad) < 0) {
        alert("Los días de inactividad no pueden ser negativos");
        return;
    }

    if (costo !== "" && parseFloat(costo) < 0) {
        alert("El costo no puede ser negativo");
        return;
    }

    const formData = new FormData(form);

    try {
        const response = await fetch("../controllers/procesar_reparacion.php", {
            method: "POST",
            body: formData,
        });

        if (response.ok) {
            document.getElementById("modalReparacion").style.display = "none";
            
            // Recargar la página completa para obtener los datos actualizados
            window.location.reload();
        } else {
            alert("Error al guardar la reparación");
        }
    } catch (error) {
        console.error("Error guardando reparación:", error);
        alert("Error al guardar la reparación: " + error.message);
    }
}

// ============================
// Editar reparación
// ============================
export async function editarReparacion(id) {
    try {
        console.log("Editando reparación ID:", id);

        // Buscar el botón con este ID para obtener los datos
        const btn = document.querySelector(`[data-id="${id}"]`);
        if (!btn) {
            throw new Error("No se encontraron los datos de la reparación");
        }

        // Llenar formulario con datos de los atributos del botón
        document.getElementById("modal-title").textContent = "Editar Reparación";
        document.getElementById("accion").value = "editar";
        document.getElementById("id_reparacion").value = btn.dataset.id;
        document.getElementById("id_activo").value = btn.dataset.idActivo;
        document.getElementById("id_lugar_reparacion").value = btn.dataset.idLugar;
        document.getElementById("id_estado_reparacion").value = btn.dataset.idEstado;
        document.getElementById("fecha").value = btn.dataset.fecha;
        document.getElementById("descripcion").value = btn.dataset.descripcion;
        document.getElementById("costo").value = btn.dataset.costo;
        
        // CORREGIDO: Manejo específico del tiempo de inactividad
        const tiempoInactividad = btn.dataset.tiempo;
        console.log("DEBUG editarReparacion: Tiempo desde dataset:", tiempoInactividad, "Tipo:", typeof tiempoInactividad);
        
        // Manejar el tiempo de inactividad correctamente
        if (tiempoInactividad === null || tiempoInactividad === undefined || tiempoInactividad === '' || tiempoInactividad === 'null' || tiempoInactividad === 'undefined') {
            document.getElementById("tiempo_inactividad").value = '';
            console.log("DEBUG editarReparacion: Estableciendo tiempo como vacío");
        } else {
            // Asegurarse de que se muestre incluso cuando es 0
            document.getElementById("tiempo_inactividad").value = tiempoInactividad;
            console.log("DEBUG editarReparacion: Estableciendo tiempo como:", tiempoInactividad);
        }

        document.getElementById("modalReparacion").style.display = "block";
        
    } catch (error) {
        console.error("Error cargando reparación:", error);
        alert("Error al cargar los datos de la reparación: " + error.message);
    }
}

// ============================
// Eliminar reparación
// ============================
export async function eliminarReparacion(id) {
    if (!confirm("¿Está seguro de eliminar esta reparación?")) return;

    try {
        const formData = new FormData();
        formData.append('accion', 'eliminar');
        formData.append('id_reparacion', id);

        const response = await fetch("../controllers/procesar_reparacion.php", {
            method: "POST",
            body: formData,
        });

        if (response.ok) {
            // Recargar la página para ver los cambios
            window.location.reload();
        } else {
            alert("Error al eliminar la reparación");
        }
    } catch (error) {
        console.error("Error eliminando reparación:", error);
        alert("Error al eliminar la reparación: " + error.message);
    }
}

// Mostrando contenido para revisar funcionalidad de modales
console.log("Revisando archivo core de reparaciones...");