// Mostrar el modal
const modal = document.getElementById("modalArea");
const btnNuevo = document.getElementById("btnNuevo");
const spanClose = document.querySelector(".close");

btnNuevo.onclick = () => modal.style.display = "block";

// Cerrar modal con clic esc o en la X
spanClose.onclick = () => modal.style.display = "none";
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.style.display === "block") {
        modal.style.display = "none";
    }
});

// Filtro de búsqueda
const buscador = document.getElementById("buscador");
const filas = document.querySelectorAll("#tablaAreas tbody tr");

// Filtrar las filas de la tabla según el texto ingresado en el buscador
buscador.addEventListener("input", function () {
    const valor = buscador.value.toLowerCase();
    filas.forEach(function (fila) {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(valor) ? "" : "none";
    });
});

document.querySelectorAll(".btn-editar").forEach(button => {
    button.addEventListener("click", () => {
        // Setear valores al formulario
        document.getElementById("accion").value = "editar";
        document.getElementById("modal-title").textContent = "Editar Área";
        document.getElementById("id_area").value = button.dataset.id;
        document.getElementById("nombre").value = button.dataset.nombre;
        
        // Mostrar el modal
        document.getElementById("modalArea").style.display = "block";
    });
});
