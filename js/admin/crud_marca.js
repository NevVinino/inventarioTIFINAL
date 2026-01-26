document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalMarca");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formMarca");
    const alerta = document.getElementById("alertaCRUD");

    // Helper: mostrar alerta por 5 segundos
    function mostrarAlerta(el, tipo, mensaje, duracion = 5000, onHide) {
        if (!el) {
            alert(mensaje);
            if (onHide) setTimeout(onHide, duracion);
            return;
        }
        el.className = (tipo === 'exito') ? 'alerta-exito' : 'alerta-error';
        el.textContent = mensaje;
        el.style.display = 'block';
        setTimeout(() => {
            el.style.display = 'none';
            if (onHide) onHide();
        }, duracion);
    }

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Marca por Tipo";
        document.getElementById("accion").value = "crear";
        form.reset();
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
            document.getElementById("modal-title").textContent = "Editar Registro de Marca por Tipo";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_marca").value = btn.dataset.id;
            document.getElementById("nombre").value = btn.dataset.nombre;
            document.getElementById("id_tipo_marca").value = btn.dataset.idTipoMarca;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaMarcas tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });

    // Interceptar eliminación para manejar errores de FK y mostrar alertas
    const deleteForms = document.querySelectorAll('form[action$="procesar_marca.php"][method="POST"]');

    deleteForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            // Respetar confirm existente
            const confirmed = window.confirm('¿Eliminar esta marca?');
            if (!confirmed) {
                e.preventDefault();
                return;
            }

            e.preventDefault();
            try {
                const fd = new FormData(form);
                const resp = await fetch(form.action, { method: 'POST', body: fd });
                const text = await resp.text();

                // Detectar error de restricción FK (SQL Server)
                const esFKError = /SQLSTATE\s*[:]?\s*23000|code\]\s*=>\s*547|DELETE statement conflicted/i.test(text);

                if (esFKError) {
                    mostrarAlerta(alerta, 'error', 'No se puede eliminar: el registro está en uso por otras tablas.', 5000);
                    return;
                }

                // Si no es error conocido, asumir éxito y recargar tras 5s
                mostrarAlerta(alerta, 'exito', 'Registro eliminado correctamente.', 5000, () => window.location.reload());
            } catch (err) {
                mostrarAlerta(alerta, 'error', 'Error de red o servidor al eliminar.', 5000);
            }
        });
    });
});