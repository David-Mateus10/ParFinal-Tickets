function buildHeaders(options, token) {
    const headers = { 'Content-Type': 'application/json' };

    if (options.headers) Object.assign(headers, options.headers);
    if (token && !options.skipAuth) headers['Authorization'] = token;

    return headers;
}

///////GENERADOR GENERAL DE REQUEST
async function apiRequest(url, options = {}) {
    const token = getToken();

    try {
        const response = await fetch(url, {
            ...options,
            headers: buildHeaders(options, token)
        });

        //// Manejo de token expirado
        if (response.status === 401 && !options.skipAuth) {
            removeToken();
            window.location.href = '../index.html';
            throw new Error('Sesión expirada');
        }

        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Error en la petición');

        return data;

    } catch (error) {
        console.error(`[API ERROR] ${url}`, error);
        throw error;
    }
}

////////////////////////////////////////////////////
//                    USERS API
////////////////////////////////////////////////////

function usersUrl(path) {
    return `${API_CONFIG.USERS_API}${path}`;
}

///////LOGIN
async function login(email, password) {
    return apiRequest(usersUrl('/login'), {
        method: 'POST',
        skipAuth: true,
        body: JSON.stringify({ email, password })
    });
}

///////REGISTER
async function register(userData) {
    return apiRequest(usersUrl('/register'), {
        method: 'POST',
        skipAuth: true,
        body: JSON.stringify(userData)
    });
}

///////LOGOUT
async function logoutAPI() {
    return apiRequest(usersUrl('/logout'), { method: 'POST' });
}

///////LIST USERS
async function listUsers() {
    return apiRequest(usersUrl('/users'), { method: 'GET' });
}

///////UPDATE USER
async function updateUser(id, userData) {
    return apiRequest(usersUrl(`/users/${id}`), {
        method: 'PUT',
        body: JSON.stringify(userData)
    });
}

///////CHANGE ROLE
async function changeUserRole(id, role) {
    return apiRequest(usersUrl(`/users/${id}/role`), {
        method: 'PATCH',
        body: JSON.stringify({ role })
    });
}

///////DELETE USER
async function deleteUser(id) {
    return apiRequest(usersUrl(`/users/${id}`), {
        method: 'DELETE'
    });
}

////////////////////////////////////////////////////
//                    TICKETS API
////////////////////////////////////////////////////

function ticketsUrl(path) {
    return `${API_CONFIG.TICKETS_API}${path}`;
}

///////CREATE TICKET
async function createTicket(ticketData) {
    return apiRequest(ticketsUrl('/tickets'), {
        method: 'POST',
        body: JSON.stringify(ticketData)
    });
}

///////LIST MY TICKETS
async function listMyTickets() {
    return apiRequest(ticketsUrl('/tickets/my'), { method: 'GET' });
}

///////LIST ALL TICKETS
async function listAllTickets() {
    return apiRequest(ticketsUrl('/tickets'), { method: 'GET' });
}

///////DETAILS TICKET
async function getTicketDetails(id) {
    return apiRequest(ticketsUrl(`/tickets/${id}`), { method: 'GET' });
}

///////UPDATE STATUS
async function updateTicketStatus(id, estado) {
    return apiRequest(ticketsUrl(`/tickets/${id}/status`), {
        method: 'PUT',
        body: JSON.stringify({ estado })
    });
}

///////ASSIGN TICKET
async function assignTicket(id, adminId) {
    return apiRequest(ticketsUrl(`/tickets/${id}/assign`), {
        method: 'PUT',
        body: JSON.stringify({ admin_id: adminId })
    });
}

///////ADD COMMENT
async function addComment(id, mensaje) {
    return apiRequest(ticketsUrl(`/tickets/${id}/comments`), {
        method: 'POST',
        body: JSON.stringify({ mensaje })
    });
}

///////GET TICKET HISTORY
async function getTicketHistory(id) {
    return apiRequest(ticketsUrl(`/tickets/${id}/history`), { method: 'GET' });
}

///////SEARCH TICKETS
async function searchTickets(filters) {
    const query = new URLSearchParams(filters).toString();
    return apiRequest(ticketsUrl(`/tickets/search?${query}`), { method: 'GET' });
}