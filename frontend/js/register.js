/* =======================
   Lógica de registro de usuario
   ======================= */
document.addEventListener("DOMContentLoaded", () => {
    // Si ya hay sesión activa, redirigir al panel correspondiente
    if (sesionActiva()) {
        const rol = rolUsuario();
        if (rol === "gestor") {
            window.location.href = "gestor-dashboard.html";
        } else if (rol === "admin") {
            window.location.href = "admin-dashboard.html";
        }
        return;
    }

    const formRegistro = document.getElementById("registerForm");
    const inputNombre = document.getElementById("name");
    const inputCorreo = document.getElementById("email");
    const inputClave = document.getElementById("password");
    const selectRol = document.getElementById("role");
    const btnRegistrar = document.getElementById("submitBtn");
    const divMensaje = document.getElementById("message");

    formRegistro.addEventListener("submit", async (e) => {
        e.preventDefault();
        limpiarMensaje();

        const nombre = inputNombre.value.trim();
        const correo = inputCorreo.value.trim();
        const clave = inputClave.value.trim();
        const rol = selectRol.value;

        // Validaciones
        if (!nombre || !correo || !clave || !rol) {
            mostrarMensaje("Todos los campos son obligatorios", "error");
            return;
        }

        if (!validarCorreo(correo)) {
            mostrarMensaje("Correo inválido", "error");
            return;
        }

        if (clave.length < 6) {
            mostrarMensaje("La contraseña debe tener mínimo 6 caracteres", "error");
            return;
        }

        if (!["gestor", "admin"].includes(rol)) {
            mostrarMensaje("Selecciona un rol válido", "error");
            return;
        }

        setCargando(true);

        try {
            const datos = { name: nombre, email: correo, password: clave, role: rol };
            await registrarUsuario(datos);

            mostrarMensaje("Registro exitoso. Redirigiendo al inicio...", "success");

            setTimeout(() => {
                window.location.href = "../index.html";
            }, 1500);
        } catch (err) {
            console.error("Error en registro:", err);
            mostrarMensaje(err.message || "No se pudo registrar el usuario", "error");
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
        btnRegistrar.disabled = estado;
        btnRegistrar.classList.toggle("btn-loading", estado);
    }

    function validarCorreo(correo) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
    }
});