// catalogos.js
// 📌 Funciones para cargar catálogos (activos, lugares, estados, tipos de cambio)

export async function cargarCatalogos() {
    try {
        // Solo intentar cargar catálogos si los elementos existen
        // ya que pueden estar pre-cargados desde PHP
        
        const selectActivos = document.getElementById("id_activo");
        const selectLugares = document.getElementById("id_lugar_reparacion");
        const selectEstados = document.getElementById("id_estado_reparacion");
        
        // Si los selects ya tienen opciones (cargadas desde PHP), no hacer nada
        if (selectActivos && selectActivos.options.length > 1) {
            console.log("Catálogos ya cargados desde PHP");
            return;
        }

        // Si no hay opciones, intentar cargar via AJAX como respaldo
        console.log("Intentando cargar catálogos via AJAX...");
        
        if (selectActivos && selectActivos.options.length <= 1) {
            await cargarActivos();
        }
        
        if (selectLugares && selectLugares.options.length <= 1) {
            await cargarLugares();
        }
        
        if (selectEstados && selectEstados.options.length <= 1) {
            await cargarEstados();
        }
        
        // Los tipos de cambio se cargan cuando se necesiten en hardware
        
    } catch (error) {
        console.warn("Error cargando catálogos via AJAX:", error.message);
        // No lanzar error ya que los datos pueden estar en PHP
    }
}

// ============================
// Cargar activos
// ============================
async function cargarActivos() {
    try {
        console.log("Cargando activos...");
        const response = await fetch("../controllers/procesar_reparacion.php?action=get_activos");
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const text = await response.text();
        
        if (!text.trim()) {
            console.warn("Respuesta vacía del servidor para activos");
            return;
        }

        const activos = JSON.parse(text);
        if (activos.success === false) throw new Error(activos.message);

        const selectActivos = document.getElementById("id_activo");
        if (selectActivos) {
            selectActivos.innerHTML = '<option value="">Seleccionar activo...</option>';
            if (Array.isArray(activos)) {
                activos.forEach((activo) => {
                    selectActivos.innerHTML += `<option value="${activo.id_activo}">${activo.nombre_equipo} (${activo.tipo_activo})</option>`;
                });
            }
        }
    } catch (error) {
        console.warn("Error cargando activos:", error.message);
    }
}

// ============================
// Cargar lugares
// ============================
async function cargarLugares() {
    try {
        console.log("Cargando lugares...");
        const response = await fetch("../controllers/procesar_reparacion.php?action=get_lugares");
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const text = await response.text();
        
        if (!text.trim()) {
            console.warn("Respuesta vacía del servidor para lugares");
            return;
        }

        const lugares = JSON.parse(text);
        if (lugares.success === false) throw new Error(lugares.message);

        const selectLugares = document.getElementById("id_lugar_reparacion");
        if (selectLugares) {
            selectLugares.innerHTML = '<option value="">Seleccionar lugar...</option>';
            if (Array.isArray(lugares)) {
                lugares.forEach((lugar) => {
                    selectLugares.innerHTML += `<option value="${lugar.id_lugar}">${lugar.nombre_lugar}</option>`;
                });
            }
        }
    } catch (error) {
        console.warn("Error cargando lugares:", error.message);
    }
}

// ============================
// Cargar estados
// ============================
async function cargarEstados() {
    try {
        console.log("Cargando estados...");
        const response = await fetch("../controllers/procesar_reparacion.php?action=get_estados");
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const text = await response.text();
        
        if (!text.trim()) {
            console.warn("Respuesta vacía del servidor para estados");
            return;
        }

        const estados = JSON.parse(text);
        if (estados.success === false) throw new Error(estados.message);

        const selectEstados = document.getElementById("id_estado_reparacion");
        if (selectEstados) {
            selectEstados.innerHTML = '<option value="">Seleccionar estado...</option>';
            if (Array.isArray(estados)) {
                estados.forEach((estado) => {
                    selectEstados.innerHTML += `<option value="${estado.id_estado_reparacion}">${estado.nombre_estado}</option>`;
                });
            }
        }
    } catch (error) {
        console.warn("Error cargando estados:", error.message);
    }
}

// ============================
// Cargar tipos de cambio (para hardware)
// ============================
export async function cargarTiposCambio() {
    try {
        console.log("Cargando tipos de cambio...");
        const response = await fetch("../controllers/procesar_reparacion.php?action=get_tipos_cambio");
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const text = await response.text();
        
        if (!text.trim()) {
            console.warn("Respuesta vacía del servidor para tipos de cambio");
            return;
        }

        const tiposCambio = JSON.parse(text);
        if (tiposCambio.success === false) throw new Error(tiposCambio.message);

        const selectTiposCambio = document.getElementById("idTipoCambio");
        if (selectTiposCambio) {
            selectTiposCambio.innerHTML = '<option value="">Seleccionar tipo...</option>';
            if (Array.isArray(tiposCambio)) {
                tiposCambio.forEach((tipo) => {
                    selectTiposCambio.innerHTML += `<option value="${tipo.id_tipo_cambio}">${tipo.nombre_tipo_cambio}</option>`;
                });
            }
        }
    } catch (error) {
        console.warn("Error cargando tipos de cambio:", error.message);
    }
}