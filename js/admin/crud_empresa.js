document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalEmpresa");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formEmpresa");

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar empresa";
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
            document.getElementById("modal-title").textContent = "Editar empresa";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_empresa").value = btn.getAttribute("data-id");
            document.getElementById("nombre").value = btn.getAttribute("data-nombre");

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaEmpresas tbody tr");
    // Filtrar las filas de la tabla según el texto ingresado en el buscador
    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });

});