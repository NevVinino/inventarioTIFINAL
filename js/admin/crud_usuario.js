// Mostrar el modal
const modal = document.getElementById("modalUsuario");
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
const filas = document.querySelectorAll("#tablaUsuarios tbody tr");

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
        document.getElementById("modal-title").textContent = "Editar usuario";
        document.getElementById("id_usuario").value = button.dataset.id;
        document.getElementById("username").value = button.dataset.username;
        document.getElementById("password").value = "";  // Si no deseas mostrar la actual
        document.getElementById("id_rol").value = button.dataset.id_rol;
        document.getElementById("id_estado_usuario").value = button.dataset.id_estado;

        // Mostrar el modal
        document.getElementById("modalUsuario").style.display = "block";
    });
});
