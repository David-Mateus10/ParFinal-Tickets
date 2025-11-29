/* =======================
   Función base de peticiones
   ======================= */
async function requestAPI(endpoint, options = {}) {
    try {
        const token = getToken();

        const headers = {
            "Content-Type": "application/json",
            ...(options.headers || {})
        };

        if (token && !options.skipAuth) {
            headers["Authorization"] = token;
        }

        const config = { ...options, headers };
        const res = await fetch(endpoint, config);

        if (res.status === 401 && !options.skipAuth) {
            removeToken();
            window.location.href = "../index.html";
            throw new Error("Sesión expirada");
        }

        const data = await res.json();
        if (!res.ok) throw new Error(data.error || "Error en la petición");

        return data;
    } catch (err) {
        console.error("Error en requestAPI:", err);
        throw err;
    }
}

/* =======================
   Usuarios
   ======================= */
async function iniciarSesion(correo, clave) {
    return await requestAPI(`${API_CONFIG.USERS_API}/login`, {
        method: "POST",
        body: JSON.stringify({ email: correo, password: clave }),
        skipAuth: true
    });
}

async function registrarUsuario(datos) {
    return await requestAPI(`${API_CONFIG.USERS_API}/register`, {
        method: "POST",
        body: JSON.stringify(datos),
        skipAuth: true
    });
}

async function cerrarSesion() {
    return await requestAPI(`${API_CONFIG.USERS_API}/logout`, { method: "POST" });
}

async function obtenerUsuarios() {
    return await requestAPI(`${API_CONFIG.USERS_API}/users`, { method: "GET" });
}

async function modificarUsuario(id, datos) {
    return await requestAPI(`${API_CONFIG.USERS_API}/users/${id}`, {
        method: "PUT",
        body: JSON.stringify(datos)
    });
}

async function cambiarRolUsuario(id, rol) {
    return await requestAPI(`${API_CONFIG.USERS_API}/users/${id}/role`, {
        method: "PATCH",
        body: JSON.stringify({ role: rol })
    });
}

async function eliminarUsuario(id) {
    return await requestAPI(`${API_CONFIG.USERS_API}/users/${id}`, { method: "DELETE" });
}

/* =======================
   Tickets
   ======================= */
async function crearTicket(datos) {
    return await requestAPI(`${API_CONFIG.TICKETS_API}/tickets`, {
        method: "POST",
        body: JSON.stringify(datos)
    });
}

async function misTickets() {
    return await requestAPI(`${API_CONFIG.TICKETS_API}/tickets/mios`, { method: "GET" });
}

async function todosLosTickets() {
    return await requestAPI(`${API_CONFIG.TICKETS_API}/tickets`, { method: "GET" });
}

async function detalleTicket(id) {
    return await requestAPI(`${API_CONFIG.TICKETS_API}/tickets/${id}`, { method: "GET" });
}

async function actualizarEstadoTicket(id, estado) {
    return await requestAPI(`${API_CONFIG.TICKETS_API}/tickets/${id}/estado`, {
        method: "PUT",
        body: JSON.stringify({ estado })
    });
}

async function asignarTicket(id, adminId) {
    return await requestAPI(`${API_CONFIG.TICKETS_API}/tickets/${id}/asignar`, {
        method: "PUT",
        body: JSON.stringify({ admin_id: adminId })
    });
}

async function agregarComentario(id, mensaje) {
    return await requestAPI(`${API_CONFIG.TICKETS_API}/tickets/${id}/comentar`, {
        method: "POST",
        body: JSON.stringify({ mensaje })
    });
}

async function historialTicket(id) {
    return await requestAPI(`${API_CONFIG.TICKETS_API}/tickets/${id}/historial`, { method: "GET" });
}

async function buscarTickets(filtros) {
    const params = new URLSearchParams(filtros);
    return await requestAPI(`${API_CONFIG.TICKETS_API}/tickets/filtrar?${params}`, { method: "GET" });
}