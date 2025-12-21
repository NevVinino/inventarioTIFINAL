document.addEventListener("DOMContentLoaded", function () {
    // --- elementos principales ---
    const modalIngreso = document.getElementById("modalIngreso");
    const modalSalida = document.getElementById("modalSalida");
    const modalEditar = document.getElementById("modalEditar");
    const btnIngresar = document.getElementById("btnIngresar");
    const formIngreso = document.getElementById("formIngreso");
    const formSalida = document.getElementById("formSalida");
    const formEditar = document.getElementById("formEditar");
    const buscador = document.getElementById("buscador");

    // --- abrir modal ingreso ---
    if (btnIngresar) {
        btnIngresar.addEventListener("click", function () {
            if (!modalIngreso) return;
            
            if (formIngreso) formIngreso.reset();
            
            // Establecer fecha actual
            const fechaActual = new Date().toISOString().split('T')[0];
            document.getElementById("fecha_ingreso").value = fechaActual;
            
            modalIngreso.style.display = "block";
        });
    }

    // --- cerrar modales ---
    document.querySelectorAll(".close").forEach(closeBtn => {
        closeBtn.addEventListener("click", function() {
            if (modalIngreso) modalIngreso.style.display = "none";
            if (modalSalida) modalSalida.style.display = "none";
            if (modalEditar) modalEditar.style.display = "none";
        });
    });

    // --- cerrar modales con tecla Escape ---
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            if (modalIngreso && modalIngreso.style.display === "block") {
                modalIngreso.style.display = "none";
            }
            if (modalSalida && modalSalida.style.display === "block") {
                modalSalida.style.display = "none";
            }
            if (modalEditar && modalEditar.style.display === "block") {
                modalEditar.style.display = "none";
            }
        }
    });


    // --- botones editar ---
    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalEditar) return;

            const idHistorial = this.dataset.id;
            const idActivo = this.dataset.activo;
            const idAlmacen = this.dataset.almacen;
            const fechaIngreso = this.dataset.fechaIngreso;
            const fechaSalida = this.dataset.fechaSalida;
            const observaciones = this.dataset.observaciones;

            document.getElementById("editar_id_historial").value = idHistorial;
            document.getElementById("editar_id_activo").value = idActivo;
            document.getElementById("editar_id_almacen").value = idAlmacen;
            document.getElementById("editar_fecha_ingreso").value = fechaIngreso;
            document.getElementById("editar_fecha_salida").value = fechaSalida || '';
            document.getElementById("editar_observaciones").value = observaciones || '';

            modalEditar.style.display = "block";
        });
    });

    // --- botones salida ---
    document.querySelectorAll(".btn-salida").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalSalida) return;

            const idHistorial = this.dataset.id;
            const activo = this.dataset.activo;
            const almacen = this.dataset.almacen;

            document.getElementById("salida_id_historial").value = idHistorial;
            document.getElementById("salida_activo").textContent = activo;
            document.getElementById("salida_almacen").textContent = almacen;

            // Establecer fecha actual
            const fechaActual = new Date().toISOString().split('T')[0];
            document.getElementById("fecha_salida").value = fechaActual;

            modalSalida.style.display = "block";
        });
    });

    // --- buscador ---
    if (buscador) {
        const filas = document.querySelectorAll("#tablaHistorial tbody tr");
        buscador.addEventListener("input", function () {
            const valor = buscador.value.toLowerCase();
            filas.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(valor) ? "" : "none";
            });
        });
    }

    // --- validación formulario ingreso ---
    if (formIngreso) {
        formIngreso.addEventListener("submit", function(event) {
            const activo = document.getElementById("id_activo").value;
            const almacen = document.getElementById("id_almacen").value;
            const fechaIngreso = document.getElementById("fecha_ingreso").value;

            if (!activo || !almacen || !fechaIngreso) {
                event.preventDefault();
                alert("Todos los campos obligatorios deben ser completados.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaIngreso > hoy) {
                event.preventDefault();
                alert("La fecha de ingreso no puede ser posterior a hoy.");
                return false;
            }

            if (!confirm("¿Confirma el ingreso de este activo al almacén?")) {
                event.preventDefault();
                return false;
            }
        });
    }

    // --- validación formulario salida ---
    if (formSalida) {
        formSalida.addEventListener("submit", function(event) {
            const fechaSalida = document.getElementById("fecha_salida").value;

            if (!fechaSalida) {
                event.preventDefault();
                alert("La fecha de salida es obligatoria.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaSalida > hoy) {
                event.preventDefault();
                alert("La fecha de salida no puede ser posterior a hoy.");
                return false;
            }

            if (!confirm("¿Confirma la salida de este activo del almacén?")) {
                event.preventDefault();
                return false;
            }
        });
    }

    // --- validación formulario editar ---
    if (formEditar) {
        formEditar.addEventListener("submit", function(event) {
            const activo = document.getElementById("editar_id_activo").value;
            const almacen = document.getElementById("editar_id_almacen").value;
            const fechaIngreso = document.getElementById("editar_fecha_ingreso").value;
            const fechaSalida = document.getElementById("editar_fecha_salida").value;

            if (!activo || !almacen || !fechaIngreso) {
                event.preventDefault();
                alert("Los campos activo, almacén y fecha de ingreso son obligatorios.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaIngreso > hoy) {
                event.preventDefault();
                alert("La fecha de ingreso no puede ser posterior a hoy.");
                return false;
            }

            if (fechaSalida && fechaSalida > hoy) {
                event.preventDefault();
                alert("La fecha de salida no puede ser posterior a hoy.");
                return false;
            }

            if (fechaSalida && fechaSalida < fechaIngreso) {
                event.preventDefault();
                alert("La fecha de salida no puede ser anterior a la fecha de ingreso.");
                return false;
            }

            if (!confirm("¿Confirma la actualización de este registro?")) {
                event.preventDefault();
                return false;
            }
        });
    }

    // --- auto-hide mensajes ---
    const alertaExito = document.querySelector(".alerta-exito");
    const mensajeError = document.querySelector(".mensaje-error");
    
    if (alertaExito) {
        setTimeout(() => {
            alertaExito.style.display = "none";
        }, 2000); // 2 segundos para alertas de éxito
    }
    
    if (mensajeError) {
        setTimeout(() => {
            mensajeError.style.display = "none";
        }, 8000); // 8 segundos para mensajes de error (se mantiene)
    }

    // --- limpiar parámetros URL ---
    if (window.location.search) {
        if (history.replaceState) {
            const url = new URL(window.location);
            url.search = '';
            window.history.replaceState({}, document.title, url.pathname);
        }
    }

    console.log("✅ Sistema de historial de almacén cargado correctamente");
});
