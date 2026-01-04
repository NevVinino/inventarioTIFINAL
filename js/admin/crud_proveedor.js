document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("modalProveedor");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formProveedor");

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
        document.getElementById("modalTitle").textContent = "Registrar Proveedor";
        document.getElementById("accion").value = "crear";
        form.reset();
        modal.style.display = "block";
    });

    //Cerrar el modal con clic esc o en la X
    spanClose.onclick = () => modal.style.display = "none";
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && modal.style.display === "block") {
            modal.style.display = "none";
        }
    });

    document.querySelectorAll(".btn-editar").forEach(function(btn) {
        btn.addEventListener("click", function() {
            document.getElementById("modalTitle").textContent = "Editar Proveedor";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_proveedor").value = btn.dataset.id;
            document.getElementById("nombre").value = btn.dataset.nombre;
            document.getElementById("RUC").value = btn.dataset.ruc;
            document.getElementById("telefono").value = btn.dataset.telefono;
            document.getElementById("email").value = btn.dataset.email;
            document.getElementById("direccion").value = btn.dataset.direccion;
            document.getElementById("ciudad").value = btn.dataset.ciudad;
            document.getElementById("pais").value = btn.dataset.pais;
            modal.style.display = "block";
        });
    });
    
    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaProveedores tbody tr");

    buscador.addEventListener("input", function() {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function(fila) {
            const nombre = fila.querySelector("td:nth-child(2)").textContent.toLowerCase();
            fila.style.display = nombre.includes(valor) ? "" : "none";
        });
    });
});
