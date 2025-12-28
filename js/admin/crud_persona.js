document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalPersona");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formPersona");

    // Verificar si hay un mensaje de error en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const errorMsg = urlParams.get('error');
    if (errorMsg) {
        alert(errorMsg);
        // Limpiar el parámetro de la URL sin recargar la página
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // === Lógica para habilitar/deshabilitar "Jefe Inmediato" según tipo de persona ===
    const tipoSelect = document.getElementById("id_tipo_persona"); // Changed from id_tipo
    const jefeSelect = document.getElementById("jefe_inmediato");

    function actualizarJefeInmediato() {
        const tipoTexto = tipoSelect.options[tipoSelect.selectedIndex].text.toLowerCase();
        if (tipoTexto === "gerente") {
            jefeSelect.value = "";
            jefeSelect.disabled = true;
        } else {
            jefeSelect.disabled = false;
        }
        filtrarOpcionesJefe();
    }

    tipoSelect.addEventListener("change", actualizarJefeInmediato);
    
    // === Lógica para filtrar opciones de jefe inmediato según tipo de persona ===
    function filtrarOpcionesJefe() {
    const tipoPersona = tipoSelect.options[tipoSelect.selectedIndex].text.toLowerCase();

    Array.from(jefeSelect.options).forEach(option => {
        const tipoJefe = option.dataset.tipo; // viene del <option data-tipo="...">

        if (!tipoJefe) return; // opción "-- Sin jefe --", siempre visible

        // Reglas de jerarquía
        if (tipoPersona === "personal") {
            // Personal puede tener como jefe a jefe de área o gerencia
            option.hidden = false;
        } else if (tipoPersona === "jefe area") {
            // Jefe de área solo puede tener como jefe a gerente
            option.hidden = (tipoJefe !== "gerente");
        } else if (tipoPersona === "gerente" ) {
            // Gerente no debe tener jefe, combo ya se desactiva en actualizarJefeInmediato()
            option.hidden = false;
        }
    });
    
}

    
    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar persona";
        document.getElementById("accion").value = "crear";
        form.reset();
        actualizarJefeInmediato(); // asegurar ejecutar la restricción de jefe inmediato
        modal.style.display = "block";
    });

    // Cerrar modal con clic esc o en la X
    spanClose.onclick = () => modal.style.display = "none";
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && modal.style.display === "block") {
            modal.style.display = "none";
        }
    });

    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.getElementById("modal-title").textContent = "Editar persona";
            document.getElementById("accion").value = "editar";


            document.getElementById("id_persona").value = btn.dataset.id;
            document.getElementById("nombre").value = btn.dataset.nombre;
            document.getElementById("apellido").value = btn.dataset.apellido;
            document.getElementById("correo").value = btn.dataset.correo;
            
            document.getElementById("celular").value = btn.dataset.celular;
            document.getElementById("jefe_inmediato").value = btn.dataset.jefe;


            document.getElementById("id_tipo_persona").value = btn.dataset.tipo; // Changed from id_tipo
            actualizarJefeInmediato(); // asegurar ejecutar la restricción de jefe inmediato
            document.getElementById("id_situacion_personal").value = btn.dataset.situacion;
            document.getElementById("id_localidad").value = btn.dataset.localidad;
            document.getElementById("id_area").value = btn.dataset.area;
            document.getElementById("id_empresa").value = btn.dataset.empresa;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaPersonas tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });
});


