document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalLocalidad");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formLocalidad");

    // Asegurar que el modal quede cerrado al cargar, evitando que el overlay oscurezca la vista
    if (modal) modal.style.display = "none";

    // Verificar si hay un mensaje de error en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const errorMsg = urlParams.get('error');
    if (errorMsg) {
        alert(errorMsg);
        if (modal) modal.style.display = "none"; // evitar overlay visible tras la alerta
        // Limpiar el parámetro de la URL sin recargar la página
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar localidad";
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
            document.getElementById("modal-title").textContent = "Editar localidad";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_localidad").value = btn.dataset.id;
            document.getElementById("localidad_nombre").value = btn.dataset.localidad_nombre;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaLocalidades tbody tr");
    // Filtrar las filas de la tabla según el texto ingresado en el buscador
    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });
});