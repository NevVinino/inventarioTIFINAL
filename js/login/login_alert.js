// /js/login_alert.js
document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get("error");
    const errorMessage = document.getElementById("error-message");

    // Mostrar alertas según el tipo de error
    if (error === "credenciales") {
        if (errorMessage) {
            errorMessage.textContent = "⚠️ Usuario o contraseña incorrectos.";
            errorMessage.style.display = "block";
        } else {
            alert("❌ Usuario o contraseña incorrectos.");
        }
    } else if (error === "deshabilitado") {
        if (errorMessage) {
            errorMessage.textContent = "⚠️ Este usuario está deshabilitado. Contacta al administrador.";
            errorMessage.style.display = "block";
        } else {
            alert("⚠️ Este usuario está deshabilitado. Contacta al administrador.");
        }
    } else if (error === "no_autenticado") {
        if (errorMessage) {
            errorMessage.textContent = "🔒 Debes iniciar sesión para acceder a esta página.";
            errorMessage.setAttribute("data-type", "info");
            errorMessage.style.display = "block";
        } else {
            alert("🔒 Debes iniciar sesión para acceder a esta página.");
        }
    } else if (error === "no_autorizado") {
        if (errorMessage) {
            errorMessage.textContent = "⛔ No tienes permisos para acceder a esta página. Inicia sesión con la cuenta correcta.";
            errorMessage.setAttribute("data-type", "warning");
            errorMessage.style.display = "block";
        } else {
            alert("⛔ No tienes permisos para acceder a esta página.");
        }
    } else if (error === "sin_rol") {
        if (errorMessage) {
            errorMessage.textContent = "⚠️ Tu cuenta no tiene un rol asignado. Contacta al administrador.";
            errorMessage.style.display = "block";
        } else {
            alert("⚠️ Tu cuenta no tiene un rol asignado. Contacta al administrador.");
        }
    } else if (error === "rol_no_valido") {
        if (errorMessage) {
            errorMessage.textContent = "⚠️ Tu rol no tiene acceso al sistema.";
            errorMessage.style.display = "block";
        } else {
            alert("⚠️ Tu rol no tiene acceso al sistema.");
        }
    }

    // Limpia los parámetros de la URL sin recargar
    if (error) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Validación del formulario del lado del cliente
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", (e) => {
            const username = document.getElementById("username").value.trim();
            const password = document.getElementById("password").value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                if (errorMessage) {
                    errorMessage.textContent = "⚠️ Por favor, completa todos los campos.";
                    errorMessage.style.display = "block";
                } else {
                    alert("⚠️ Por favor, completa todos los campos.");
                }
                return false;
            }
            
            // Ocultar mensaje de error antes del envío
            if (errorMessage) {
                errorMessage.style.display = "none";
            }
        });
    }
});
