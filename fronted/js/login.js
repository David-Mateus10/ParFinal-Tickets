/* =======================
   Lógica de inicio de sesión
   ======================= */
document.addEventListener("DOMContentLoaded", () => {
    // Si ya hay sesión activa, redirigir al panel correspondiente
    if (sesionActiva()) {
        const rol = getUserRole();
        if (rol === "gestor") {
            window.location.href = "front/gestor-dashboard.html";
        } else if (rol === "admin") {
            window.location.href = "front/admin-dashboard.html";
        }
        return;
    }

    const formLogin = document.getElementById("loginForm");
    const inputCorreo = document.getElementById("email");
    const inputClave = document.getElementById("password");
    const btnAcceder = document.getElementById("submitBtn");
    const divMensaje = document.getElementById("message");

    formLogin.addEventListener("submit", async (e) => {
        e.preventDefault();
        limpiarMensaje();

        const correo = inputCorreo.value.trim();
        const clave = inputClave.value.trim();

        // Validaciones básicas
        if (!correo || !clave) {
            mostrarMensaje("Todos los campos son obligatorios", "error");
            return;
        }

        if (!validarCorreo(correo)) {
            mostrarMensaje("Formato de correo inválido", "error");
            return;
        }

        setCargando(true);

        try {
            // Consumir API de login
            const respuesta = await iniciarSesion(correo, clave);

            if (respuesta.token) saveToken(respuesta.token);
            if (respuesta.user) saveUserInfo(respuesta.user);

            mostrarMensaje("Acceso correcto. Redirigiendo...", "success");

            setTimeout(() => {
                const rol = getUserRole();
                if (rol === "gestor") {
                    window.location.href = "front/gestor-dashboard.html";
                } else if (rol === "admin") {
                    window.location.href = "front/admin-dashboard.html";
                } else {
                    mostrarMensaje("Rol no reconocido", "error");
                    setCargando(false);
                }
            }, 1200);
        } catch (err) {
            console.error("Error en inicio de sesión:", err);
            mostrarMensaje(err.message || "Credenciales inválidas", "error");
            setCargando(false);
        }
    });

    /* =======================
       Funciones auxiliares
       ======================= */
    function mostrarMensaje(texto, tipo) {
        divMensaje.textContent = texto;
        divMensaje.className = `auth-message ${tipo} show`;
    }

    function limpiarMensaje() {
        divMensaje.className = "auth-message";
        divMensaje.textContent = "";
    }

    function setCargando(estado) {
        btnAcceder.disabled = estado;
        btnAcceder.classList.toggle("btn-loading", estado);
    }

    function validarCorreo(correo) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
    }
});
