/* =======================
   Módulo de autenticación
   ======================= */

// Guardar token
function guardarToken(token) {
    localStorage.setItem("token_sesion", token);
}

// Obtener token
function obtenerToken() {
    return localStorage.getItem("token_sesion");
}

// Eliminar token y datos de usuario
function limpiarSesion() {
    localStorage.removeItem("token_sesion");
    localStorage.removeItem("rol_usuario");
    localStorage.removeItem("nombre_usuario");
    localStorage.removeItem("id_usuario");
}

// Guardar datos del usuario
function guardarUsuario(usuario) {
    if (usuario.role) localStorage.setItem("rol_usuario", usuario.role);
    if (usuario.name) localStorage.setItem("nombre_usuario", usuario.name);
    if (usuario.id) localStorage.setItem("id_usuario", usuario.id);
}

// Obtener rol
function rolUsuario() {
    return localStorage.getItem("rol_usuario");
}

// Obtener nombre
function nombreUsuario() {
    return localStorage.getItem("nombre_usuario");
}

// Obtener ID
function idUsuario() {
    return localStorage.getItem("id_usuario");
}

// Verificar si hay sesión activa
function sesionActiva() {
    return obtenerToken() !== null;
}

// Verificar autenticación y redirigir
function verificarSesion() {
    if (!sesionActiva()) {
        window.location.href = "../index.html";
        return false;
    }
    return true;
}

// Verificar rol y redirigir si no coincide
function verificarRol(rolRequerido) {
    const rol = rolUsuario();
    if (rol !== rolRequerido) {
        alert("Acceso denegado");
        if (rol === "gestor") {
            window.location.href = "gestor-dashboard.html";
        } else if (rol === "admin") {
            window.location.href = "admin-dashboard.html";
        } else {
            window.location.href = "../index.html";
        }
        return false;
    }
    return true;
}

// Cerrar sesión
async function cerrarSesion() {
    try {
        await fetch(`${API_CONFIG.USERS_API}/logout`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": obtenerToken()
            }
        });
    } catch (err) {
        console.error("Error al cerrar sesión:", err);
    } finally {
        limpiarSesion();
        window.location.href = "../index.html";
    }
}
